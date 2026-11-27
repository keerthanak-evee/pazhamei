<?php
/**
 * Tests for MovedPages: /about-contact and /enquire redirect to /about-us (owner decision, design update).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Access\MovedPages;
use PHPUnit\Framework\TestCase;

/**
 * Redirect targets and link rewriting.
 */
final class MovedPagesTest extends TestCase {

	private const HOME = 'http://pazhamei.local';

	public function test_about_contact_goes_to_about_us(): void {
		$this->assertSame( '/about-us/', MovedPages::target( '/about-contact/' ) );
		$this->assertSame( '/about-us/', MovedPages::target( '/about-contact' ) );
	}

	public function test_enquire_goes_to_the_contact_section(): void {
		$this->assertSame( '/about-us/#contact', MovedPages::target( '/enquire/' ) );
	}

	public function test_enquire_keeps_the_plan(): void {
		$this->assertSame( '/about-us/?plan=6-day#contact', MovedPages::target( '/enquire/?plan=6-day' ) );
		$this->assertSame( '/about-us/?plan=trial#contact', MovedPages::target( '/enquire?plan=trial' ) );
	}

	public function test_other_pages_do_not_move(): void {
		foreach ( array( '/', '/about-us/', '/plans/', '/enquire-now/', '/enquire/extra/', '/faq/?enquire=1', '/account/plans/' ) as $uri ) {
			$this->assertSame( '', MovedPages::target( $uri ), $uri );
		}
	}

	public function test_site_in_a_folder(): void {
		$this->assertSame( '/about-us/#contact', MovedPages::target( '/shop-site/enquire/', '/shop-site/' ) );
		$this->assertSame( '', MovedPages::target( '/enquire/', '/shop-site/' ) );
	}

	public function test_rewrites_relative_links(): void {
		$html = '<a href="/enquire">Send</a> <a href="/enquire?plan=26-day">Enquire</a> <a href="/about-contact">Story</a>';

		$this->assertSame(
			'<a href="/about-us/#contact">Send</a> <a href="/about-us/?plan=26-day#contact">Enquire</a> <a href="/about-us/">Story</a>',
			MovedPages::rewrite_links( $html, self::HOME )
		);
	}

	public function test_rewrites_absolute_links_and_menu_urls(): void {
		$menu = '<!-- wp:navigation-link {"label":"ContactUs","url":"http://pazhamei.local/about-contact/","kind":"post-type"} /-->';

		$this->assertSame(
			'<!-- wp:navigation-link {"label":"ContactUs","url":"http://pazhamei.local/about-us/","kind":"post-type"} /-->',
			MovedPages::rewrite_links( $menu, self::HOME . '/' )
		);
		$this->assertSame(
			'<a href="http://pazhamei.local/about-us/#contact">x</a>',
			MovedPages::rewrite_links( '<a href="http://pazhamei.local/enquire/">x</a>', self::HOME )
		);
	}

	public function test_leaves_other_links_alone(): void {
		$html = '<a href="/enquire-now">a</a> <a href="https://elsewhere.example/enquire">b</a> <a href="/plans">c</a> <p>/enquire in text</p>';

		$this->assertSame( $html, MovedPages::rewrite_links( $html, self::HOME ) );
	}
}
