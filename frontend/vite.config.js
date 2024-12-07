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
const buildDir = path.join(buildParent, 'maltyst');
const backendDir = path.resolve(__dirname, '..', 'backend');
const FILES_TO_COPY = ['html-views', 'mjml', 'src', 'vendor', 'maltyst.php'];

// Ensure required directories exist
if (!fs.existsSync(buildParent)) {
  fs.mkdirSync(buildParent, { recursive: true });
}

// Generate distributable archive helper
const generateArchive = async () => {
  try {
    const archivePath = path.join(buildParent, 'maltyst.zip');
    console.log(`[INFO] Generating archive at: ${archivePath}`);

    // Copy necessary files
    await Promise.all(
      FILES_TO_COPY.map(async (file) => {
        const srcDir = path.join(backendDir, file);
        const destDir = path.join(buildDir, file);
        if (fs.existsSync(srcDir)) {
          await fse.copy(srcDir, destDir);
          console.log(`[INFO] Copied ${srcDir} to ${destDir}`);
        } else {
          console.warn(`[WARNING] ${srcDir} does not exist and will not be copied.`);
        }
      })
    );

    // Create the archive
    const output = fs.createWriteStream(archivePath);
    const archive = archiver('zip');
    archive.pipe(output);
    archive.directory(buildDir, false);

    await archive.finalize();
    console.log(`[INFO] Archive generated successfully: ${archivePath}`);
  } catch (err) {
    console.error(`[ERROR] Error generating archive:`, err);
  }
};

// Custom Vite Plugin
const createArchivePlugin = () => ({
  name: 'vite-plugin-generate-archive',
  closeBundle: async () => {
    console.log(`[INFO] Running archive generation plugin...`);
    await generateArchive();
  },
});

export default defineConfig(({ mode }) => {
  console.log(`[INFO] Vite build mode: ${mode}`);

  const modeConfigs = {
    sandbox: {
      build: {
        emptyOutDir: false,
        sourcemap: true,
      },
    },
    production: {
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
    },
  };

  const commonConfig = {
    root: __dirname,
    build: {
      manifest: true,
      outDir: buildDir,
      rollupOptions: {
        input: {
          'js-maltyst': resolve(__dirname, './js/maltyst.mjs'),
          'style-maltyst': resolve(__dirname, './scss/maltyst.scss'),
        },
      },
    },
    plugins: [createArchivePlugin()],
  };

  // Merge common config with mode-specific config
  const mergedOptions = merge(commonConfig, modeConfigs[mode] || {});
  console.log(`[INFO] Final merged configuration:`, mergedOptions);

  return mergedOptions;
});