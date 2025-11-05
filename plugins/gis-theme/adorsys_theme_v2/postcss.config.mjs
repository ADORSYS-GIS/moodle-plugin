import tailwindcssPostcss from "@tailwindcss/postcss";
import autoprefixer from "autoprefixer";
import cssnano from "cssnano";
import postcssImport from "postcss-import";
import postcssNested from "postcss-nested";

export default {
  plugins: [
    postcssImport(),
    tailwindcssPostcss(),
    postcssNested(),
    autoprefixer(),
    cssnano({ preset: "default" })
  ]
};
