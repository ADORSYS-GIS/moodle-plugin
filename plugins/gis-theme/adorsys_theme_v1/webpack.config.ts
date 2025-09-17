import { fileURLToPath } from 'url';
import path from 'path';
import { Configuration } from 'webpack';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';
import CopyPlugin from 'copy-webpack-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// 👇 Set the final build destination
const OUTPUT_PATH = path.resolve(__dirname, '../../../../moodle-plugin/outputs/plugins/gis-theme/adorsys_theme_v1');
console.log("Output Path:", OUTPUT_PATH);

const config: Configuration = {
  mode: 'production',
  devtool: 'source-map',

  //  Entry points for JS and CSS
  entry: {
    bundle: './src/index.ts',
  },

  output: {
    path: OUTPUT_PATH,
    filename: 'js/[name].js',
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
