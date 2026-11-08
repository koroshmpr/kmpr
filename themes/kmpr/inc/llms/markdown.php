<?php
/**
 * Minimal, dependency-free HTML → Markdown converter for the LLMs module.
 *
 * Loaded lazily (only while building llms-full.txt or serving a .md URL), so it costs nothing on normal page loads.
 * Handles what the site's content actually uses: headings, paragraphs, lists (nested), links, emphasis,
 * inline code, fenced code blocks, tables, blockquotes, images, figures, details/summary and rules.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

class KMPR_LLMs_Markdown
{
    /** @var int Heading level shift (h2 + 1 = ###). */
    private $shift = 0;

    /** @var int Minimum heading level (content headings never outrank the item title). */
    private $min = 1;

    /** @var bool Keep images as ![alt](src). */
    private $images = true;

    /** @var string Base URL for relative links. */
    private $base = '';

    /**
     * Convert HTML to Markdown.
     *
     * @param string $html   HTML fragment.
     * @param array  $args   shift (int), images (bool).
     */
    public static function convert($html, $args = array())
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        $self         = new self();
        $self->shift  = (int) ($args['shift'] ?? 0);
        $self->images = (bool) ($args['images'] ?? true);
        $self->min    = max(1, (int) ($args['min'] ?? 1));
        $self->base   = untrailingslashit(home_url('/'));

        if (!class_exists('DOMDocument')) {
            return trim(html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES, 'UTF-8'));
        }

        $prev = libxml_use_internal_errors(true);
        $dom  = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="kmpr-md-root">' . $html . '</div>', LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementById('kmpr-md-root');
        if (!$root) {
            return trim(wp_strip_all_tags($html));
        }
        $md = $self->children($root, 0);
        return self::tidy($md);
    }

    /** Collapse blank lines and trailing spaces. */
    public static function tidy($md)
    {
        $lines = explode("\n", (string) $md);
        $fence = false;
        foreach ($lines as $i => $line) {
            if (strpos(ltrim($line), '```') === 0) {
                $fence = !$fence;
                continue;
            }
            if (!$fence) {
                // Collapse runs of spaces left by inline elements (keep list indentation).
                $line      = preg_replace('/(?<=\S) {2,}/u', ' ', $line);
                $line      = preg_replace('/ +([،؛:.!?؟)])/u', '$1', $line);
                $lines[$i] = rtrim(preg_match('/^\s+[-\d]/', $line) ? $line : ltrim($line));
            }
        }
        $md = preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines));
        return trim($md);
    }

    private function children(DOMNode $node, $depth)
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= $this->node($child, $depth);
        }
        return $out;
    }

    private function inline(DOMNode $node)
    {
        return trim(preg_replace('/\s+/u', ' ', $this->children($node, 0)));
    }

    private function node(DOMNode $n, $depth)
    {
        if ($n->nodeType === XML_TEXT_NODE || $n->nodeType === XML_CDATA_SECTION_NODE) {
            return preg_replace('/\s+/u', ' ', $n->textContent);
        }
        if ($n->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }
        /** @var DOMElement $n */
        $tag = strtolower($n->nodeName);

        switch ($tag) {
            case 'script':
            case 'style':
            case 'noscript':
            case 'svg':
            case 'template':
            case 'form':
            case 'button':
            case 'input':
            case 'select':
            case 'textarea':
            case 'iframe':
            case 'video':
            case 'audio':
            case 'canvas':
                return '';

            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $level = min(6, max($this->min, (int) substr($tag, 1) + $this->shift));
                $text  = $this->inline($n);
                return $text === '' ? '' : "\n\n" . str_repeat('#', $level) . ' ' . $text . "\n\n";

            case 'p':
            case 'div':
            case 'section':
            case 'article':
            case 'header':
            case 'footer':
            case 'main':
            case 'aside':
            case 'figure':
            case 'dl':
                $inner = trim($this->children($n, $depth));
                return $inner === '' ? '' : "\n\n" . $inner . "\n\n";

            case 'details':
                return "\n\n" . trim($this->children($n, $depth)) . "\n\n";

            case 'summary':
            case 'dt':
                $t = $this->inline($n);
                return $t === '' ? '' : "\n\n**" . $t . "**\n\n";

            case 'dd':
            case 'figcaption':
                $t = $this->inline($n);
                return $t === '' ? '' : "\n\n" . ($tag === 'figcaption' ? '*' . $t . '*' : $t) . "\n\n";

            case 'br':
                return "\n";

            case 'hr':
                return "\n\n---\n\n";

            case 'strong':
            case 'b':
                $t = $this->inline($n);
                return $t === '' ? '' : ' **' . $t . '** ';

            case 'em':
            case 'i':
                $t = $this->inline($n);
                return $t === '' ? '' : ' *' . $t . '* ';

            case 'del':
            case 's':
                $t = $this->inline($n);
                return $t === '' ? '' : '~~' . $t . '~~';

            case 'code':
                $t = trim($n->textContent);
                if ($t === '') {
                    return '';
                }
                $fence = strpos($t, '`') !== false ? '``' : '`';
                return ' ' . $fence . $t . $fence . ' ';

            case 'pre':
                $code = rtrim($n->textContent);
                $lang = '';
                $el   = $n->getElementsByTagName('code')->item(0);
                foreach (array($n, $el) as $c) {
                    if ($c instanceof DOMElement && preg_match('/(?:language|lang)-([a-z0-9+#-]+)/i', $c->getAttribute('class'), $m)) {
                        $lang = strtolower($m[1]);
                        break;
                    }
                }
                return "\n\n```" . $lang . "\n" . $code . "\n```\n\n";

            case 'a':
                $text = $this->inline($n);
                $href = trim($n->getAttribute('href'));
                if ($text === '') {
                    return '';
                }
                if ($href === '' || $href[0] === '#' || stripos($href, 'javascript:') === 0) {
                    return $text;
                }
                return '[' . str_replace(array('[', ']'), array('(', ')'), $text) . '](' . $this->abs($href) . ')';

            case 'img':
                if (!$this->images) {
                    return '';
                }
                $src = trim($n->getAttribute('src') ?: $n->getAttribute('data-src'));
                $alt = trim(preg_replace('/\s+/u', ' ', $n->getAttribute('alt')));
                if ($src === '' || strpos($src, 'data:') === 0) {
                    return '';
                }
                return '![' . $alt . '](' . $this->abs($src) . ')';

            case 'ul':
            case 'ol':
                return "\n\n" . $this->listing($n, $depth) . "\n\n";

            case 'li':
                // Stray <li> outside a list.
                return "\n- " . $this->inline($n) . "\n";

            case 'blockquote':
                $inner = self::tidy($this->children($n, $depth));
                if ($inner === '') {
                    return '';
                }
                return "\n\n" . preg_replace('/^/m', '> ', $inner) . "\n\n";

            case 'table':
                return "\n\n" . $this->table($n) . "\n\n";

            default:
                return $this->children($n, $depth);
        }
    }

    private function listing(DOMElement $list, $depth)
    {
        $ordered = strtolower($list->nodeName) === 'ol';
        $i       = max(1, (int) ($list->getAttribute('start') ?: 1));
        $indent  = str_repeat('  ', $depth);
        $lines   = array();
        foreach ($list->childNodes as $li) {
            if (!($li instanceof DOMElement) || strtolower($li->nodeName) !== 'li') {
                continue;
            }
            $text   = '';
            $nested = '';
            foreach ($li->childNodes as $c) {
                if ($c instanceof DOMElement && in_array(strtolower($c->nodeName), array('ul', 'ol'), true)) {
                    $nested .= "\n" . $this->listing($c, $depth + 1);
                } else {
                    $text .= $this->node($c, $depth + 1);
                }
            }
            // Paragraphs inside list items become a single line.
            $text    = trim(preg_replace('/\s+/u', ' ', $text));
            $marker  = $ordered ? ($i++) . '. ' : '- ';
            $lines[] = $indent . $marker . $text . $nested;
        }
        return implode("\n", $lines);
    }

    private function table(DOMElement $table)
    {
        $rows = array();
        foreach ($table->getElementsByTagName('tr') as $tr) {
            $cells = array();
            foreach ($tr->childNodes as $cell) {
                if ($cell instanceof DOMElement && in_array(strtolower($cell->nodeName), array('td', 'th'), true)) {
                    $cells[] = str_replace('|', '\|', $this->inline($cell));
                }
            }
            if ($cells) {
                $rows[] = $cells;
            }
        }
        if (!$rows) {
            return '';
        }
        $cols = max(array_map('count', $rows));
        $out  = array();
        foreach ($rows as $i => $cells) {
            $cells = array_pad($cells, $cols, '');
            $out[] = '| ' . implode(' | ', $cells) . ' |';
            if ($i === 0) {
                $out[] = '|' . str_repeat(' --- |', $cols);
            }
        }
        return implode("\n", $out);
    }

    private function abs($url)
    {
        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
        if (preg_match('#^(https?:|mailto:|tel:)#i', $url)) {
            return str_replace(array(' ', ')'), array('%20', '%29'), $url);
        }
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        return $this->base . '/' . ltrim($url, '/');
    }
}
