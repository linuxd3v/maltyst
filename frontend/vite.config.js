import { defineConfig } from 'vite';
import path, { resolve } from 'path';
import fs from 'fs';
import fse from 'fs-extra';
import archiver from 'archiver';
import brotli from "rollup-plugin-brotli";
import zlib from "zlib";
import gzipPlugin from 'rollup-plugin-gzip';
import { merge } from 'lodash-es';

const buildParent = path.resolve(__dirname, '..', 'plugin-dist');
console.log(buildParent);
const buildDir = `${buildParent}/maltyst`;
console.log(buildDir);
const backendDir = path.resolve(__dirname, '..', 'backend');

// Generate distributable archive helper
const generateArchive = () => {
  const archivePath = `${buildParent}/maltyst.zip`;

  console.log(`Generating archive at: ${archivePath}`);

  const filesToCopy = ['html-views', 'mjml', 'src', 'vendor', 'maltyst.php'];

  filesToCopy.forEach((file) => {
    const srcDir = `${backendDir}/${file}`;
    const destDir = `${buildDir}/${file}`;
    fse.copySync(srcDir, destDir);
  });

  const output = fs.createWriteStream(archivePath);
  const archive = archiver('zip');

  archive.pipe(output);
  archive.directory(buildDir, false);
  archive.finalize();

  console.log(`Generated archive at: ${archivePath}`);
};

// Custom Vite Plugin
const archivePlugin = () => ({
  name: 'generate-archive',
  closeBundle: async () => {
    generateArchive();
  },
});

export default defineConfig(({ mode }) => {
  const isSandbox = mode === 'sandbox';

  const commonConfig = {
    root: __dirname,
    build: {
      manifest: true,
      outDir: buildDir,
      rollupOptions: {
        input: {
          'js-maltyst': resolve(__dirname, './js/maltyst.js'),
          'style-maltyst': resolve(__dirname, './scss/maltyst.scss'),
        },
      },
    },
    plugins: [
      archivePlugin(),
    ],
  };

  const sandboxConfig = {
    build: {
      emptyOutDir: false,
      sourcemap: true,
    },
  };

  const productionConfig = {
    build: {
      emptyOutDir: true,
      sourcemap: false,
    },
    plugins: [
      brotli({
        test: /\.(js|css|html|txt|xml|json|svg)$/,
        options: {
          params: {
            [zlib.constants.BROTLI_PARAM_MODE]: zlib.constants.BROTLI_MODE_GENERIC,
            [zlib.constants.BROTLI_PARAM_QUALITY]: 7,
          },
        },
        minSize: 1000,
      }),
      gzipPlugin({
        gzipOptions: {
          level: 9,
          minSize: 1000,
        },
      }),
    ],
  };

  const mergedOptions = merge(
    commonConfig,
    isSandbox ? sandboxConfig : productionConfig
  );

  return mergedOptions;
});
