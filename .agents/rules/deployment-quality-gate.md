# Deployment Quality Gate Rule

This rule is active for all pair programming tasks in this project.

## Quality Gate Checklist

Every change intended for deployment must pass the 4-point verification checklist before pushing to git:

1. **Appearance (ظاهر):** Buttons must not wrap text vertically; SVGs must have explicit width/height attributes to prevent flex blowout; layout must be balanced without awkward empty voids.
2. **SEO (سئو):** Valid Schema.org JSON-LD microdata; native Jalali Persian dates; crawlable pagination links; semantic headings.
3. **Errors (ارور):** Production build passes cleanly; no missing files or CSS classes; no FTP deployment 550 errors in GitHub Actions.
4. **Responsiveness (ریسپانسیو):** Clean scaling across mobile, tablet, and desktop without horizontal scroll overflow.

