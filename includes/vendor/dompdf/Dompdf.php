<?php
namespace Dompdf;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Extremely small Dompdf subset capable of rendering structured text into PDFs.
 */
class Dompdf {
    /**
     * Dompdf options.
     *
     * @var Options
     */
    protected $options;

    /**
     * HTML payload to render.
     *
     * @var string
     */
    protected $html = '';

    /**
     * Paper size in points (width, height).
     *
     * @var array
     */
    protected $paperSize = array( 595.28, 841.89 );

    /**
     * Page orientation.
     *
     * @var string
     */
    protected $orientation = 'portrait';

    /**
     * Generated PDF binary output.
     *
     * @var string
     */
    protected $pdf = '';

    /**
     * Constructor.
     *
     * @param Options|null $options Dompdf options.
     */
    public function __construct( ?Options $options = null ) {
        $this->options = $options ?: new Options();
    }

    /**
     * Load HTML markup to be rendered.
     *
     * @param string $html     HTML content.
     * @param string $encoding Optional encoding, defaults to UTF-8.
     */
    public function loadHtml( $html, $encoding = 'UTF-8' ) {
        $html     = (string) $html;
        $encoding = (string) $encoding;

        if ( '' !== $encoding && 0 !== strcasecmp( $encoding, 'UTF-8' ) && function_exists( 'mb_convert_encoding' ) ) {
            $html = mb_convert_encoding( $html, 'UTF-8', $encoding );
        }

        $this->html = $html;
    }

    /**
     * Configure paper size and orientation.
     *
     * @param string|array $size        Named size or [width, height] dimensions.
     * @param string       $orientation Orientation string.
     */
    public function setPaper( $size, $orientation = 'portrait' ) {
        $this->paperSize   = $this->normalizePaperSize( $size );
        $this->orientation = ( 'landscape' === strtolower( (string) $orientation ) ) ? 'landscape' : 'portrait';
    }

    /**
     * Render the loaded HTML to PDF.
     */
    public function render() {
        $html  = $this->sanitizeHtml( $this->html );
        $lines = $this->extractLines( $html );

        if ( empty( $lines ) ) {
            $lines = array( '' );
        }

        $this->pdf = $this->buildPdf( $lines );
    }

    /**
     * Retrieve the generated PDF.
     *
     * @return string
     */
    public function output() {
        return (string) $this->pdf;
    }

    /**
     * Remove high-risk nodes from the HTML payload.
     *
     * @param string $html Input HTML.
     *
     * @return string
     */
    protected function sanitizeHtml( $html ) {
        $html = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $html );
        $html = preg_replace( '#<style\b[^>]*>.*?</style>#is', '', $html );

        return (string) $html;
    }

    /**
     * Extract printable lines from HTML markup.
     *
     * @param string $html Sanitised HTML string.
     *
     * @return array
     */
    protected function extractLines( $html ) {
        $dom = new DOMDocument( '1.0', 'UTF-8' );

        $internal_errors = libxml_use_internal_errors( true );
        $markup          = trim( (string) $html );

        if ( '' === $markup ) {
            $markup = '<html><body></body></html>';
        } else {
            $markup = '<?xml encoding="UTF-8">' . $markup;
        }

        $dom->loadHTML( $markup );
        libxml_clear_errors();
        libxml_use_internal_errors( $internal_errors );

        $body = $dom->getElementsByTagName( 'body' )->item( 0 );
        if ( ! $body ) {
            return array();
        }

        $lines = array();
        $this->collectLines( $body, $lines );

        $normalized = array();
        foreach ( $lines as $line ) {
            $line = $this->normalizeLine( $line );
            if ( '' === $line ) {
                continue;
            }

            foreach ( $this->wrapLine( $line ) as $segment ) {
                $segment = $this->normalizeLine( $segment );

                if ( '' !== $segment ) {
                    $normalized[] = $segment;
                }
            }
        }

        $result = array();
        foreach ( $normalized as $line ) {
            if ( empty( $result ) || end( $result ) !== $line ) {
                $result[] = $line;
            }
        }

        return $result;
    }

    /**
     * Recursively collect text content in document order.
     *
     * @param DOMNode $node  Current DOM node.
     * @param array   $lines Accumulator.
     */
    protected function collectLines( DOMNode $node, array &$lines ) {
        if ( $node instanceof DOMElement ) {
            $name = strtolower( $node->tagName );

            if ( in_array( $name, array( 'script', 'style' ), true ) ) {
                return;
            }

            if ( 'table' === $name ) {
                $lines = array_merge( $lines, $this->extractTableLines( $node ) );

                return;
            }

            if ( $node->hasAttribute( 'data-wr-line' ) ) {
                $text = $this->normalizeLine( $node->textContent );
                if ( '' !== $text ) {
                    $lines[] = $text;
                }

                return;
            }

            if ( in_array( $name, array( 'h1', 'h2', 'h3', 'p', 'li' ), true ) ) {
                $text = $this->normalizeLine( $node->textContent );
                if ( '' !== $text ) {
                    if ( 'h1' === $name ) {
                        $text = $this->toUpper( $text );
                    }

                    $lines[] = $text;
                }

                return;
            }
        }

        foreach ( $node->childNodes as $child ) {
            if ( $child instanceof DOMNode ) {
                $this->collectLines( $child, $lines );
            }
        }
    }

    /**
     * Extract tabular data into printable rows.
     *
     * @param DOMElement $table Table element.
     *
     * @return array
     */
    protected function extractTableLines( DOMElement $table ) {
        $lines  = array();
        $xpath  = new DOMXPath( $table->ownerDocument );
        $header = array();

        foreach ( $xpath->query( './/thead//th', $table ) as $th ) {
            $text = $this->normalizeLine( $th->textContent );
            if ( '' !== $text ) {
                $header[] = $text;
            }
        }

        if ( $header ) {
            $header_line = implode( ' | ', $header );
            $lines[]     = $header_line;
            $lines[]     = str_repeat( '-', min( 120, max( 20, strlen( $header_line ) ) ) );
        }

        foreach ( $xpath->query( './/tbody//tr', $table ) as $tr ) {
            $row = array();
            foreach ( $xpath->query( './/td', $tr ) as $td ) {
                $text = $this->normalizeLine( $td->textContent );
                if ( '' !== $text ) {
                    $row[] = $text;
                }
            }

            if ( $row ) {
                $lines[] = implode( ' | ', $row );
            }
        }

        foreach ( $xpath->query( './/tfoot//tr', $table ) as $tr ) {
            $row = array();
            foreach ( $xpath->query( './/td', $tr ) as $td ) {
                $text = $this->normalizeLine( $td->textContent );
                if ( '' !== $text ) {
                    $row[] = $text;
                }
            }

            if ( $row ) {
                $lines[] = implode( ' | ', $row );
            }
        }

        return $lines;
    }

    /**
     * Normalize whitespace within a line of text.
     *
     * @param string $text Raw text.
     *
     * @return string
     */
    protected function normalizeLine( $text ) {
        $text = preg_replace( '/\s+/u', ' ', (string) $text );

        return trim( $text );
    }

    /**
     * Convert a string to uppercase while preserving UTF-8 characters.
     *
     * @param string $text Input string.
     *
     * @return string
     */
    protected function toUpper( $text ) {
        if ( function_exists( 'mb_strtoupper' ) ) {
            return mb_strtoupper( $text, 'UTF-8' );
        }

        return strtoupper( $text );
    }

    /**
     * Wrap a line to fit the page width.
     *
     * @param string $line Text line.
     *
     * @return array
     */
    protected function wrapLine( $line ) {
        $line = trim( (string) $line );

        if ( '' === $line ) {
            return array();
        }

        $wrapped = wordwrap( $line, 90, "\n", true );
        $parts   = explode( "\n", $wrapped );

        return array_values( array_filter( array_map( 'trim', $parts ), 'strlen' ) );
    }

    /**
     * Translate a paper size value into dimensions in points.
     *
     * @param string|array $size Paper size.
     *
     * @return array
     */
    protected function normalizePaperSize( $size ) {
        $presets = array(
            'a4'     => array( 595.28, 841.89 ),
            'letter' => array( 612.00, 792.00 ),
        );

        if ( is_array( $size ) && count( $size ) >= 2 ) {
            $width  = max( 100, (float) $size[0] );
            $height = max( 100, (float) $size[1] );

            return array( $width, $height );
        }

        if ( is_string( $size ) ) {
            $size = strtolower( $size );

            if ( isset( $presets[ $size ] ) ) {
                return $presets[ $size ];
            }
        }

        return $presets['a4'];
    }

    /**
     * Build a minimal PDF document from text lines.
     *
     * @param array $lines Lines of text.
     *
     * @return string
     */
    protected function buildPdf( array $lines ) {
        list( $width, $height ) = $this->getPaperDimensions();

        $font_size   = 12;
        $line_height = 16;
        $margin_left = 50;
        $start_y     = $height - 50;

        $content_lines = array();
        $current_y     = $start_y;
        $first_line    = true;

        foreach ( $lines as $line ) {
            $escaped = $this->escapeText( $line );

            if ( $first_line ) {
                $content_lines[] = sprintf( '1 0 0 1 %d %.2f Tm (%s) Tj', $margin_left, $current_y, $escaped );
                $first_line      = false;
            } else {
                $content_lines[] = sprintf( '0 -%.2f Td (%s) Tj', (float) $line_height, $escaped );
            }
        }

        if ( empty( $content_lines ) ) {
            $content_lines[] = sprintf( '1 0 0 1 %d %.2f Tm () Tj', $margin_left, $current_y );
        }

        $content = "BT\n/F1 {$font_size} Tf\n" . implode( "\n", $content_lines ) . "\nET";

        $objects = array(
            '<< /Type /Catalog /Pages 2 0 R >>',
            sprintf( '<< /Type /Pages /Kids [3 0 R] /Count 1 /MediaBox [0 0 %.2f %.2f] >>', $width, $height ),
            '<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            sprintf( "<< /Length %d >>\nstream\n%s\nendstream", $this->getBinaryLength( $content ), $content ),
        );

        $pdf     = "%PDF-1.4\n";
        $offsets = array( 0 );

        foreach ( $objects as $index => $object ) {
            $offsets[ $index + 1 ] = $this->getBinaryLength( $pdf );
            $pdf                  .= sprintf( "%d 0 obj\n%s\nendobj\n", $index + 1, $object );
        }

        $xref_offset = $this->getBinaryLength( $pdf );

        $pdf .= 'xref' . "\n";
        $pdf .= '0 ' . ( count( $objects ) + 1 ) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ( $i = 1; $i <= count( $objects ); $i++ ) {
            $pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
        }

        $pdf .= "trailer\n<< /Size " . ( count( $objects ) + 1 ) . " /Root 1 0 R >>\n";
        $pdf .= 'startxref' . "\n" . $xref_offset . "\n%%EOF";

        return $pdf;
    }

    /**
     * Escape text for inclusion in PDF operators.
     *
     * @param string $text Text to escape.
     *
     * @return string
     */
    protected function escapeText( $text ) {
        $text = str_replace( '\\', '\\\\', (string) $text );
        $text = str_replace( array( '(', ')' ), array( '\\(', '\\)' ), $text );

        return $text;
    }

    /**
     * Retrieve the current paper dimensions, applying orientation if needed.
     *
     * @return array
     */
    protected function getPaperDimensions() {
        list( $width, $height ) = $this->paperSize;

        if ( 'landscape' === $this->orientation ) {
            return array( $height, $width );
        }

        return array( $width, $height );
    }

    /**
     * Determine the binary length of a string.
     *
     * @param string $content Content string.
     *
     * @return int
     */
    protected function getBinaryLength( $content ) {
        if ( function_exists( 'mb_strlen' ) ) {
            return mb_strlen( $content, '8bit' );
        }

        return strlen( $content );
    }
}
