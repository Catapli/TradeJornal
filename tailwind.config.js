import forms from "@tailwindcss/forms";
import typography from "@tailwindcss/typography";
import defaultTheme from "tailwindcss/defaultTheme";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./public/datatable/**",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
                poppins: ["Poppins", "sans-serif"],
            },
            colors: {
                primary: "#0033a1",
                secondary: "#333333",
                secondaryhover: "#202020",
                backbuttons: "#0000ff29",
            },
            height: {
                97: "388px", // Un poco más grande que h-96
                100: "400px", // Valores intermedios si lo deseas
                110: "440px",
                120: "480px",
                125: "500px", // 500px exactos
            },
        },
    },

    plugins: [forms, typography],
};
