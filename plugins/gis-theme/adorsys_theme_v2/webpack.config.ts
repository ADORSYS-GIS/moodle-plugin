import path from 'path';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';
import CopyPlugin from 'copy-webpack-plugin';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const OUTPUT_PATH = path.resolve(__dirname, '../../../outputs/plugins/gis-theme/adorsys_theme_v2');

export default {
  mode: 'production',
  devtool: 'source-map',
  entry: {
    bundle: './src/index.ts',
  },
  output: {
    path: OUTPUT_PATH,
    filename: 'js/[name].js',
    clean: true,
    libraryTarget: 'amd',
  },
  resolve: {
    extensions: ['.ts', '.js', '.scss'],
    alias: {
      'theme_adorsys_theme_v2': path.resolve(__dirname, 'src/'),
      'theme_adorsys_theme_v1': path.resolve(__dirname, '../adorsys_theme_v1/src/'),
    },
  },
  module: {
    rules: [
      {
        test: /\.(ts|js)$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env', '@babel/preset-typescript'],
          },
        },
      },
      {
        test: /\.(s[ac]ss|css)$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          'postcss-loader',
          'sass-loader',
        ],
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'style/[name].css',
    }),
    new CopyPlugin({
      patterns: [
        { from: 'templates', to: 'templates' },
        { from: 'layout', to: 'layout' },
        { from: 'pix', to: 'pix' },
        { from: 'lang', to: 'lang' },
        {
          from: '**/*.php',
          globOptions: {
            ignore: ['node_modules/**', 'src/**']
          },
          noErrorOnMissing: true
        }
      ]
    })
  ],
  externals: {
    'core/ajax': 'core/ajax',
    'core/notification': 'core/notification',
  },
};