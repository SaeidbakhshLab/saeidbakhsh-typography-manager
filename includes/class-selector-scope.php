<?php
/**
 * Keep generated typography outside the WordPress administration toolbar.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class Selector_Scope {
	/**
	 * Scope each validated selector without increasing its specificity.
	 *
	 * The guard belongs on the originating element, before any pseudo-element.
	 * Commas inside functions, quoted attributes and escapes are preserved.
	 *
	 * @param string $selector Validated selector list.
	 * @return string
	 */
	public static function without_admin_bar( $selector ) {
		$guard  = ':not(:where(#wpadminbar, #wpadminbar *))';
		$result = array();
		foreach ( Element_Registry::selector_parts( $selector ) as $part ) {
			$position    = strlen( $part );
			$parentheses = 0;
			$brackets    = 0;
			$quote       = '';
			$escaped     = false;
			$length      = strlen( $part );
			for ( $index = 0; $index < $length; $index++ ) {
				$character = $part[ $index ];
				if ( $escaped ) {
					$escaped = false;
					continue;
				}
				if ( '\\' === $character ) {
					$escaped = true;
					continue;
				}
				if ( '' !== $quote ) {
					if ( $character === $quote ) {
						$quote = '';
					}
					continue;
				}
				if ( '"' === $character || "'" === $character ) {
					$quote = $character;
					continue;
				}
				if ( '[' === $character ) {
					$brackets++;
				} elseif ( ']' === $character ) {
					$brackets--;
				} elseif ( 0 === $brackets && '(' === $character ) {
					$parentheses++;
				} elseif ( 0 === $brackets && ')' === $character ) {
					$parentheses--;
				}
				if ( ':' === $character && 0 === $brackets && 0 === $parentheses ) {
					$double_colon = isset( $part[ $index + 1 ] ) && ':' === $part[ $index + 1 ];
					$legacy       = preg_match( '/^:(?:before|after|first-line|first-letter)(?![a-z0-9_-])/i', substr( $part, $index ) );
					if ( $double_colon || $legacy ) {
						$position = $index;
						break;
					}
				}
			}
			$result[] = substr( $part, 0, $position ) . $guard . substr( $part, $position );
		}
		return implode( ', ', $result );
	}
}
