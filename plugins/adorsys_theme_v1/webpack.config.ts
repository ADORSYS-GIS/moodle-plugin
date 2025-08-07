import { fileURLToPath } from 'url';
import path from 'path';
import { Configuration } from 'webpack';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const config: Configuration = {
    entry: {
        main: "./src/index.ts"
    },
    devtool: "source-map",
    output: {
        clean: true,
        filename: "js/[name].js",
        path: path.resolve(__dirname, "dist"),
    },
    mode: "production",
    module: {
        rules: [
            {
                test: /\.(s|)[ca]ss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    "css-loader",
                    {
                        loader: "postcss-loader",
                        options: {
                            postcssOptions: {
                                plugins: [
                                    ["@tailwindcss/postcss", {}],
                                    ["autoprefixer", {}],
                                    ["cssnano", { preset: 'default' }]
                                ]
                            }
                        }
                    },
                    {
                        loader: "sass-loader",
                        options: {
                            api: "modern"
                        }
                    }
                ]
            }
        ]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: "../style/[name].css",
        })
    ]
};

export default config;
