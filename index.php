<?php

/*
 * Acorn routes every request through this file (template_include) and passes the real template:
 * a Blade view when one exists, otherwise WordPress's block-template canvas for templates/*.html.
 * Do not empty this file; block templates render through it.
 */

echo view(app('sage.view'), app('sage.data'))->render();
