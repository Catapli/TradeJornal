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

    safelist: [
        // Fondos
        "bg-emerald-900",
        "bg-red-900",
        // Textos
        "text-emerald-300",
        "text-red-300",
        "text-emerald-400",
        "text-red-400",
        // Bordes
        "border-emerald-700",
        "border-red-700",
        // Otros posibles
        "text-center",
        "text-right",
        "font-mono",
        "text-[#0eb90e]",
        "bg-[#00800061]",
        "text-[#eb0b0b]",
        "bg-[#7f101061]",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
                poppins: ["Poppins", "sans-serif"],
                google: ["Google Sans", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: "#0F172A",
                secondary: "#374361",
                secondaryhover: "#535a6b",
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
