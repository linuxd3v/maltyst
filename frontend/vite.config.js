import { defineConfig } from 'vite';
import { resolve } from 'path';
// import { exec } from 'child_process';
// import mjml2html from 'mjml';
import fs from 'fs';
import * as path from 'path';
import fse from 'fs-extra';
import archiver from 'archiver';
import brotli from "rollup-plugin-brotli";
import zlib from "zlib";
import gzipPlugin from 'rollup-plugin-gzip';
import { merge } from 'lodash-es';

//Theme name
const SITE_THEME_NAME = 'wpmaltyst';


//Change outDir based on theme build presence
let outDir = path.resolve(__dirname, '..', 'static') + '/dist-' + SITE_THEME_NAME


let commonConfig = {
  root: __dirname,
  build: {
    manifest: true,
    outDir: outDir,

    //Bundles are needed in all envs
    rollupOptions: {
      input: {
        'js-maltyst': resolve(__dirname, './js/maltyst.js'),

        'style-maltyst': resolve(__dirname, './scss/maltyst.scss'),

        // 'mjml-newpost': resolve(__dirname, './mjml/email-template-newpost.mjml'),
      }
      // output: {
      //   sourcemap: true,
      //   entryFileNames: 'maltyst.min.js',
      //   chunkFileNames: '[name]-[hash].js',
      //   assetFileNames: '[name]-[hash].[ext]',
      //   dir: '../dist',
      // },
    },
  },
  custom: {
    generateArchive,
  },
};

//Sandbox only config
let sandboxConfig = { 
  build: {
    emptyOutDir: false,
    sourcemap: true,
  }
};


//Production only config
let productionConfig = { 
  build: {
    emptyOutDir: true,
    sourcemap: false,
  },
  plugins: [
    brotli({
      test: /\.(js|css|html|txt|xml|json|svg)$/, // what to compress
      options: {
        params: {
          [zlib.constants.BROTLI_PARAM_MODE]: zlib.constants.BROTLI_MODE_GENERIC,
          [zlib.constants.BROTLI_PARAM_QUALITY]: 7 // turn down the quality, resulting in a faster compression (default is 11)
        }
      },

      // Ignore files smaller than this.
      //1000 is what cloudfront does: https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/ServingCompressedFiles.html
      minSize: 1000
    }),
    gzipPlugin({
      gzipOptions: {
        //Gzip compression level 
        level: 9,

        //Dont compress files that are too small as it can just make them larger
        minSize: 1000
      }
    })
  ],
};




// MJML Compilation Helper
// const compileMJML = () => {
//   const inputPath = './assets/mjml/email-template-newpost.mjml';
//   const outputPath = './dist/html/email-template-newpost.html';

//   const mjmlContent = fs.readFileSync(inputPath, 'utf-8');
//   const result = mjml2html(mjmlContent);
//   fs.mkdirSync(resolve('./dist/html'), { recursive: true });
//   fs.writeFileSync(outputPath, result.html);
//   console.log(`Compiled MJML: ${inputPath} -> ${outputPath}`);
// };

// // Plugin to trigger MJML compilation
// const viteMJMLPlugin = () => ({
//   name: 'vite-plugin-mjml',
//   buildStart() {
//     compileMJML();
//   },
//   handleHotUpdate(ctx) {
//     if (ctx.file.endsWith('.mjml')) {
//       compileMJML();
//     }
//   },
// });

// Generate distributable archive helper
const generateArchive = () => {
  const dirTmp = './plugin-dist';
  const realDir = `${dirTmp}/maltyst`;

  if (fs.existsSync(realDir)) fse.removeSync(realDir);
  fs.mkdirSync(realDir, { recursive: true });

  const filesToCopy = ['dist', 'html-views', 'src', 'vendor', 'maltyst.php', 'readme.md'];
  filesToCopy.forEach((file) => {
    const srcDir = `./${file}`;
    const destDir = `${realDir}/${file}`;
    fse.copySync(srcDir, destDir);
  });

  const archivePath = `${dirTmp}/maltyst.zip`;
  const output = fs.createWriteStream(archivePath);
  const archive = archiver('zip');

  archive.pipe(output);
  archive.directory(realDir, false);
  archive.finalize();

  console.log(`Generated archive: ${archivePath}`);
};


export default defineConfig(({ command, mode, ssrBuild }) => {
  //export default defineConfig(
  //build, sandbox, false
  console.log("Vite.js command, mode, ssrBuild: ", command, mode, ssrBuild);
  let mergedOptions = {};
  if (mode === 'sandbox') {
    mergedOptions = merge(commonConfig, sandboxConfig);
  } else {
    mergedOptions = merge(commonConfig, productionConfig);
  }
  console.log("merged options: ", mergedOptions);

  return mergedOptions
})


// Define Vite configuration
// export default defineConfig();

// CLI Script to trigger archive generation (if needed)
// if (process.env.NODE_ENV === 'production') {
//   generateArchive();
// }
