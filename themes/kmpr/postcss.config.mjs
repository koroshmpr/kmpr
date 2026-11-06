export default {
	content: [
		"./*.php",
		"./template-parts/**/*.php",
		"./inc/**/*.php",
	],
	theme: {
	},
	plugins: {
		'@tailwindcss/postcss': {},
		'postcss-nesting': {},
	},
};
