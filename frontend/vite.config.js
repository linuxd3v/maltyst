import { defineConfig } from 'vite';
import { resolve } from 'path';
import fs from 'fs';
import * as path from 'path';
import fse from 'fs-extra';
import archiver from 'archiver';
import brotli from "rollup-plugin-brotli";
import zlib from "zlib";
import gzipPlugin from 'rollup-plugin-gzip';
import { merge } from 'lodash-es';

//Build directory
const buildParent = path.resolve(__dirname, '..', 'plugin-dist');
const buildDir = buildParent + '/maltyst';
const backendDir = path.resolve(__dirname, '..', 'bakend');

let commonConfig = {
  root: __dirname,
  build: {
    manifest: true,
    outDir: buildDir,

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
      //   assetFileNames: '[name]-[hash].[ext]',buildDir
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


// Generate distributable archive helper
const generateArchive = () => {
  console.log(`Generate archive: ${archivePath}`);

  const filesToCopy = [
    'html-views', 
    'mjml', 
    'src', 
    'vendor', 
    'maltyst.php', 
  ];

  filesToCopy.forEach((file) => {
    const srcDir = `${backendDir}/${file}`;
    const destDir = `${buildDir}/${file}`;
    fse.copySync(srcDir, destDir);
  });

  const archivePath = `${buildParent}/maltyst.zip`;
  const output = fs.createWriteStream(archivePath);
  const archive = archiver('zip');

  archive.pipe(output);
  archive.directory(buildDir, false);
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
