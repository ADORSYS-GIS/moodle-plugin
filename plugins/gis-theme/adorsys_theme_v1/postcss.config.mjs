import tailwindcssPostcss from "@tailwindcss/postcss";
import autoprefixer from "autoprefixer";
import cssnano from "cssnano";

export default {
  plugins: [
    tailwindcssPostcss(),
    autoprefixer(),
    cssnano({ preset: "default" })
  ]
};
