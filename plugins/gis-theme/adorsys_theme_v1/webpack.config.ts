import { fileURLToPath } from 'url';
import path from 'path';
import { Configuration } from 'webpack';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';
import CopyPlugin from 'copy-webpack-plugin';
import { globSync } from 'glob';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// 👇 Set the final build destination
const OUTPUT_PATH = path.resolve(__dirname, '../../../../moodle-plugin/outputs/plugins/gis-theme/adorsys_theme_v1');
console.log("Output Path:", OUTPUT_PATH);

const amdEntries: Record<string, string> = {};
const amdFiles = globSync('amd/src/*.js');
amdFiles.forEach((file: string) => {
  const name = path.basename(file, '.js');
  amdEntries[`amd/build/${name}.min`] = `./${file}`;
});

const config: Configuration = {
  mode: 'production',
  devtool: 'source-map',

  //  Entry points for JS and CSS + AMD modules
  entry: {
    bundle: './src/index.ts',
    ...amdEntries,
  },

  output: {
    path: OUTPUT_PATH,
    filename: (pathData) => {
      // AMD modules go to their configured paths with .js extension
      if (pathData.chunk?.name?.startsWith('amd/build/')) {
        return '[name].js';
      }
      // Main bundle goes to tmp
      return 'tmp/[name].js';
    },
    clean: true
  },

  resolve: {
    extensions: ['.ts', '.js']
  },

  module: {
    rules: [
      {
        test: /\.(s[ac]ss|css)$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          'postcss-loader',
          'sass-loader'
        ]
      },
      {
        test: /\.ts$/,
        use: 'ts-loader',
        exclude: /node_modules/
      },
      {
        test: /\.js$/,
        include: path.resolve(__dirname, 'amd/src'),
        use: {
          loader: 'esbuild-loader',
          options: {
            loader: 'js',
            target: 'es2015'
          }
        }
      }
    ]
  },

  plugins: [
    new MiniCssExtractPlugin({
      filename: 'style/[name].css'
    }),

    //  Copy only Moodle plugin-relevant files
    new CopyPlugin({
      patterns: [
        { from: 'templates', to: 'templates' },
        { from: 'layout', to: 'layout' },
        { from: 'pix', to: 'pix' },
        { from: 'lang', to: 'lang' },
        { from: 'amd/src', to: 'amd/src', noErrorOnMissing: true },

        // Copy PHP root files like version.php, lib.php etc.
        {
          from: '**/*.php',
          globOptions: {
            ignore: ['node_modules/**', 'src/**']
          },
          noErrorOnMissing: true
        }
      ]
    })
  ]
};

export default config;
