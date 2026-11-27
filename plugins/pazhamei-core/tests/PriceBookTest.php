<?php
/**
 * Tests for Plans & Prices: checking the grid, saving it, and the price-consistency rule.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Money;
use Pazhamei\Domain\PriceGrid;
use Pazhamei\Services\PriceBook;
use Pazhamei\Tests\Fakes\MemoryPriceStore;
use PHPUnit\Framework\TestCase;

/**
 * Prices have one source (the variations), so cards, checkout and storage agree.
 */
final class PriceBookTest extends TestCase {

	/**
	 * A full, valid grid as typed by the owner.
	 *
	 * @return array<string, array<string, string>>
	 */
	private static function grid(): array {
		return array(
			'TRIAL' => array( 'VEG' => '0', 'NONVEG' => '149', 'MIXED' => '99.50' ),
			'D6'    => array( 'VEG' => '900', 'NONVEG' => '1,080', 'MIXED' => '₹990' ),
			'D26'   => array( 'VEG' => '3640', 'NONVEG' => '4420', 'MIXED' => '4030' ),
		);
	}

	public function test_saves_a_valid_grid(): void {
		$store = new MemoryPriceStore();
		$book  = new PriceBook( $store );

		$this->assertSame( array(), $book->save( self::grid() ) );
		$this->assertSame( 1080.0, $store->get( 'D6', 'NONVEG' ) );
		$this->assertSame( 990.0, $store->get( 'D6', 'MIXED' ) );
		$this->assertSame( 99.5, $store->get( 'TRIAL', 'MIXED' ) );
	}

	public function test_trial_can_be_free_but_packs_cannot(): void {
		$grid                 = self::grid();
		$grid['TRIAL']['VEG'] = '0';
		$grid['D26']['VEG']   = '0';
		$errors               = PriceGrid::check_prices( $grid )['errors'];

		$this->assertArrayNotHasKey( 'TRIAL.VEG', $errors );
		$this->assertSame( 'Must be more than ₹0.', $errors['D26.VEG'] );
	}

	public function test_rejects_bad_input_and_saves_nothing(): void {
		$store                 = new MemoryPriceStore();
		$book                  = new PriceBook( $store );
		$grid                  = self::grid();
		$grid['D6']['VEG']     = 'nine hundred';
		$grid['D6']['MIXED']   = '';
		$grid['D26']['NONVEG'] = '-5';
		$grid['D26']['MIXED']  = '9.999';
		$grid['TRIAL']['VEG']  = '250000';

		$errors = $book->save( $grid );

		$this->assertSame( 'Use numbers only, like 900 or 99.50.', $errors['D6.VEG'] );
		$this->assertSame( 'Enter a price.', $errors['D6.MIXED'] );
		$this->assertArrayHasKey( 'D26.NONVEG', $errors );
		$this->assertArrayHasKey( 'D26.MIXED', $errors );
		$this->assertArrayHasKey( 'TRIAL.VEG', $errors );
		$this->assertSame( 0.0, $store->get( 'D26', 'VEG' ), 'Nothing is saved when any cell is wrong.' );
	}

	public function test_missing_cell_is_an_error(): void {
		$grid = self::grid();
		unset( $grid['TRIAL']['NONVEG'] );
		$this->assertSame( 'Enter a price.', PriceGrid::check_prices( $grid )['errors']['TRIAL.NONVEG'] );
	}

	public function test_missing_product_is_reported(): void {
		$store                      = new MemoryPriceStore();
		$store->exists['D26.MIXED'] = false;
		$errors                     = ( new PriceBook( $store ) )->save( self::grid() );

		$this->assertStringContainsString( 'install-products', $errors['D26.MIXED'] );
	}

	/**
	 * The consistency rule: after saving, the plan card, the checkout (the
	 * stored variation price WooCommerce charges) and the admin grid all
	 * show the same number.
	 */
	public function test_cards_checkout_and_storage_always_agree(): void {
		$store = new MemoryPriceStore();
		$book  = new PriceBook( $store );
		$book->save( self::grid() );

		foreach ( PriceGrid::PLANS as $plan ) {
			$cards = $book->card_prices( $plan );
			foreach ( array( 'VEG', 'NONVEG', 'MIXED' ) as $type ) {
				$stored = $store->get( $plan, $type );

				$this->assertSame( $stored, $cards[ $type ]['amount'], "$plan $type card" );
				$this->assertSame( $stored, $book->grid()[ $plan ][ $type ], "$plan $type admin grid" );
				$this->assertSame( $stored, $book->price( $plan, $type ), "$plan $type checkout" );
				$this->assertSame( Money::rupees( $stored ), $cards[ $type ]['display'] );
			}
		}
	}

	public function test_includes_text(): void {
		$this->assertSame( array( 'One', 'Two' ), PriceGrid::check_includes( "One\r\n\n  Two  \n" )['lines'] );
		$this->assertSame( 'Add at least one line.', PriceGrid::check_includes( " \n " )['error'] );
		$this->assertNotNull( PriceGrid::check_includes( implode( "\n", range( 1, 7 ) ) )['error'] );
		$this->assertNotNull( PriceGrid::check_includes( str_repeat( 'x', 121 ) )['error'] );
	}
}
