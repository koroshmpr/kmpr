        </main>
        <footer>
            <?php
            if (function_exists('kmpr_is_games_page') && kmpr_is_games_page()) {
                get_template_part('template-parts/games/footer-bar');
                get_template_part('template-parts/games/dock'); // on single games only visible in the installed app
            } else {
                get_template_part('template-parts/layout/footer/index');
            }
            get_template_part('template-parts/layout/backToTop');
            if (is_singular('services')) {
                get_template_part('template-parts/backToArchive');
            }
            ?>
        </footer>
        <?php wp_footer(); ?>
    </body>
</html>