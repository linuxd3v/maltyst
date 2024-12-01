const gulp                 = require('gulp');
const { parallel, series } = require('gulp');

const sass       = require('gulp-sass');
const sassGlob   = require('gulp-sass-glob');
const mjml       = require('gulp-mjml');
const mjmlEngine = require('mjml')
const minimist   = require('minimist');
const babel      = require('gulp-babel');
const concat     = require('gulp-concat');
const uglify     = require('gulp-uglify');
const rename     = require('gulp-rename');


sass.compiler = require('node-sass');


var knownOptions = {
    string: 'env',
    default: { env: process.env.NODE_ENV || 'production' }
};
var options = minimist(process.argv.slice(2), knownOptions);



function mjmlCompile() {
    return gulp.src('./assets/mjml/email-template-newpost.mjml')
    .pipe(mjml())
    .pipe(gulp.dest('./dist/html'))
};

function sassCompile () {
    //console.log("Compiling scss");

    return gulp.src('./assets/scss/*.scss')
      .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
      //.pipe(sass.sync().on('error', sass.logError))
      .pipe(concat('maltyst.min.css'))
      .pipe(gulp.dest('./dist/css'));
};

function jsCompile(cb) {
    console.log('js-compile');

    return gulp.src('./assets/js/*.js', { sourcemaps: true })
        //.pipe(babel())
        .pipe(babel({
            presets: ['@babel/preset-env']
        }))
      
        .pipe(uglify())
        .pipe(concat('maltyst.min.js'))
        .pipe(gulp.dest('./dist/js/'));
}

function watchBuild() {
    //console.log("Running gulp watch")

    gulp.watch('./assets/mjml/*.mjml', series('mjml-compile'));

    gulp.watch('./assets/scss/*.scss', series('sass-compile'));

    gulp.watch('./assets/js/*.js', series('js-compile'));
};


// This is a task that should generate a packaged
// wordpress plugin (esstentialy a stripped zip archive)
function generateDistributableArchive(cb) {

    //Create a temporary directory to generate distributable at
    var fs     = require('fs');
    const path = require('path');
    const fse  = require('fs-extra');
    const archiver = require('archiver');

    var dirTmp = './plugin-dist';
    if (!fs.existsSync(dirTmp)){
        fs.mkdirSync(dirTmp);
    }
    var realDir = './plugin-dist/maltyst';
    if (fs.existsSync(realDir)) {
        fse.removeSync(realDir); 
    }
    fs.mkdirSync(realDir);


    // Copying only files that are used for WP plugins
    const maltystDirFiles = ['dist', 'html-views', 'src', 'vendor', 'maltyst.php', 'readme.md']; 
    maltystDirFiles.forEach(maltystDirFile => { 
        var srcDir  = './' + maltystDirFile;
        var destDir = realDir + '/' + maltystDirFile;

        try {
            fse.copySync(srcDir, destDir);
            console.log('success!');
        } catch (err) {
            console.error(err);
        }
        
        console.log('maltystDirFile: ' + maltystDirFile, 'srcDir:' , srcDir, 'destDir: ', destDir);
    });



    // Copmressing dir into archive
    // https://www.npmjs.com/package/archiver
    var cleanupDist = function (d) {
        //remove no longer needed directory
        if (fs.existsSync(d)) {
            fse.removeSync(d); 
        }
    };
    var output = fs.createWriteStream(dirTmp + '/maltyst.zip');
    var archive = archiver('zip');
    output.on('close', function () {
        console.log(archive.pointer() + ' total bytes');
        console.log('archiver has been finalized and the output file descriptor has closed.');

        cleanupDist(realDir);
    });
    archive.on('error', function(err){
        throw err;
    });
    
    archive.pipe(output);
    
    // append files from a sub-directory, putting its contents at the root of archive
    archive.directory(realDir, false);
    archive.finalize();




    cb();
};






//Exporting tasks
exports['mjml-compile'] = mjmlCompile;
exports['sass-compile'] = sassCompile;
exports['js-compile']   = jsCompile;
exports['gen-dist']     = generateDistributableArchive;
exports.watchbuild      = watchBuild;

//Exporting build task
exports.build           = parallel(sassCompile, jsCompile, mjmlCompile);