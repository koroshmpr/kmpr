        </main>
        <footer>
            <?php
            get_template_part('template-parts/layout/footer/index');
            get_template_part('template-parts/layout/backToTop');
            if (is_singular('services')) {
                get_template_part('template-parts/backToArchive');
            }
            ?>
        </footer>
        <?php wp_footer(); ?>
    </body>
</html>