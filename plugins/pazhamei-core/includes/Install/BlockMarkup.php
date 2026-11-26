<?php
/**
 * BlockMarkup: writes core block markup for the page installer.
 *
 * Pages are stored as block markup (the HTML with <!-- wp:... --> comments that
 * the block editor saves). These helpers write that markup exactly as the
 * editor would, so the installed pages open without "invalid block" warnings.
 *
 * Text passed in is written by us (not by visitors). Plain-text arguments are
 * escaped; arguments named $html may contain simple inline tags like <a> and <strong>.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Small builders for core blocks.
 */
final class BlockMarkup {

	/**
	 * Heading block.
	 *
	 * @param string $text  Heading text.
	 * @param int    $level 2 to 4.
	 */
	public static function heading( string $text, int $level = 2 ): string {
		$attrs = 2 === $level ? '' : ' {"level":' . $level . '}';
		return "<!-- wp:heading{$attrs} -->\n<h{$level} class=\"wp-block-heading\">" . esc_html( $text ) . "</h{$level}>\n<!-- /wp:heading -->";
	}

	/**
	 * Paragraph block.
	 *
	 * @param string $html Paragraph content (inline HTML allowed).
	 */
	public static function paragraph( string $html ): string {
		return "<!-- wp:paragraph -->\n<p>" . wp_kses_post( $html ) . "</p>\n<!-- /wp:paragraph -->";
	}

	/**
	 * The yellow "DRAFT – review before launch" note at the top of a page.
	 *
	 * @param string $text Note text.
	 */
	public static function draft_note( string $text = 'DRAFT – review before launch' ): string {
		return "<!-- wp:paragraph {\"style\":{\"typography\":{\"fontWeight\":\"700\"}},\"backgroundColor\":\"turmeric-light\"} -->\n"
			. '<p class="has-turmeric-light-background-color has-background" style="font-weight:700">' . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->";
	}

	/**
	 * Bulleted list block.
	 *
	 * @param string[] $items Items (inline HTML allowed).
	 */
	public static function list( array $items ): string {
		$out = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
		foreach ( $items as $item ) {
			$out .= "<!-- wp:list-item -->\n<li>" . wp_kses_post( $item ) . "</li>\n<!-- /wp:list-item -->";
		}
		return $out . "</ul>\n<!-- /wp:list -->";
	}

	/**
	 * Details block (one FAQ question).
	 *
	 * @param string $question Summary line.
	 * @param string $html     Answer (inline HTML allowed).
	 */
	public static function details( string $question, string $html ): string {
		return "<!-- wp:details -->\n<details class=\"wp-block-details\"><summary>" . esc_html( $question ) . '</summary>'
			. self::paragraph( $html ) . "</details>\n<!-- /wp:details -->";
	}

	/**
	 * A block that renders on the server, with no attributes (for example pz/plan-cards).
	 *
	 * @param string $name Block name.
	 * @param string $json Attributes as JSON, or ''.
	 */
	public static function dynamic( string $name, string $json = '' ): string {
		return "<!-- wp:{$name}" . ( '' !== $json ? ' ' . $json : '' ) . ' /-->';
	}

	/**
	 * Join blocks with blank lines, as the editor does.
	 *
	 * @param string[] $blocks Block markup.
	 */
	public static function join( array $blocks ): string {
		return implode( "\n\n", $blocks ) . "\n";
	}
}
