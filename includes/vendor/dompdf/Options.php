<?php
namespace Dompdf;

/**
 * Minimal Options implementation compatible with bundled Dompdf subset.
 */
class Options {
    /**
     * Stored option values.
     *
     * @var array
     */
    protected $options = array(
        'defaultFont'     => 'Helvetica',
        'isRemoteEnabled' => false,
        'dpi'             => 96,
        'chroot'          => null,
    );

    /**
     * Constructor.
     *
     * @param array $options Optional default options.
     */
    public function __construct( array $options = array() ) {
        foreach ( $options as $name => $value ) {
            $this->set( $name, $value );
        }
    }

    /**
     * Generic setter used for compatibility with the full Dompdf API.
     *
     * @param string $name  Option name.
     * @param mixed  $value Option value.
     *
     * @return $this
     */
    public function set( $name, $value ) {
        $this->options[ $name ] = $value;

        return $this;
    }

    /**
     * Retrieve an option value.
     *
     * @param string $name    Option name.
     * @param mixed  $default Default value when the option is not defined.
     *
     * @return mixed
     */
    public function get( $name, $default = null ) {
        if ( array_key_exists( $name, $this->options ) ) {
            return $this->options[ $name ];
        }

        return $default;
    }

    /**
     * Set the default font used in generated PDFs.
     *
     * @param string $font Font name.
     *
     * @return $this
     */
    public function setDefaultFont( $font ) {
        $this->options['defaultFont'] = (string) $font;

        return $this;
    }

    /**
     * Retrieve the default font name.
     *
     * @return string
     */
    public function getDefaultFont() {
        return (string) $this->options['defaultFont'];
    }

    /**
     * Enable or disable remote asset loading.
     *
     * @param bool $enabled Whether remote assets are allowed.
     *
     * @return $this
     */
    public function setIsRemoteEnabled( $enabled ) {
        $this->options['isRemoteEnabled'] = (bool) $enabled;

        return $this;
    }

    /**
     * Determine if remote assets are allowed.
     *
     * @return bool
     */
    public function isRemoteEnabled() {
        return (bool) $this->options['isRemoteEnabled'];
    }

    /**
     * Configure the rendering DPI.
     *
     * @param int $dpi Target DPI value.
     *
     * @return $this
     */
    public function setDpi( $dpi ) {
        $dpi = (int) $dpi;
        if ( $dpi <= 0 ) {
            $dpi = 96;
        }

        $this->options['dpi'] = $dpi;

        return $this;
    }

    /**
     * Retrieve the current DPI setting.
     *
     * @return int
     */
    public function getDpi() {
        return (int) $this->options['dpi'];
    }

    /**
     * Restrict file access to a specific base directory.
     *
     * @param string $path Base directory.
     *
     * @return $this
     */
    public function setChroot( $path ) {
        $this->options['chroot'] = (string) $path;

        return $this;
    }

    /**
     * Retrieve the configured chroot path.
     *
     * @return string|null
     */
    public function getChroot() {
        return $this->options['chroot'];
    }
}
