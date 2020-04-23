<?php

//header('Content-type: text/plain');

/**
 * Author: Svetoslav Marinov - https://orbisius.com
 * Sets quantity to 0 for all products variations.
 * 
 * Requires:
 * WP-CLI & WooCommerce to be installed
 * shell_exec
 */

$admin_user_id = 1;

if (!isset($_REQUEST['go'])) {
	die("Hi");
}

// https://stackoverflow.com/questions/20316338/intermittently-echo-out-data-with-long-running-php-script
if (function_exists('apache_setenv')) {
	apache_setenv( 'no-gzip', 1 );
}

@ini_set('output_buffering', 0);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

try {
	echo "<pre>";
	$user_id_esc = escapeshellarg($admin_user_id);
	echo "Starting at " . date('r') . "\n";
	$cmd_suff = "--user=$user_id_esc --skip-themes  2>&1"; // --url='https://dev.orbisius.com/store'

	// Get all products
	$parent_product_ids_json_str = trim(`wp wc product list --field=id $cmd_suff --format=json`);
	//echo $parent_product_ids_json_str;
	$parent_product_ids = empty( $parent_product_ids_json_str ) ? [] : json_decode($parent_product_ids_json_str, true);

	if ( empty( $parent_product_ids ) ) {
		throw new Exception( "No products found.\n" );
	}

	sort($parent_product_ids);
	$total = count($parent_product_ids);

	foreach ($parent_product_ids as $idx => $product_id) {
		$prod_cnt = $idx + 1;
		$product_id_esc = escapeshellarg($product_id);
		echo "<h2>[$prod_cnt/$total] Processing product id: $product_id\n</h2>";
		$parent_variations_ids_json_str = trim(`wp wc product_variation list $product_id_esc --field=id --format=json $cmd_suff`);
		//echo $parent_variations_ids_json_str;
		$parent_variations_ids = empty( $parent_variations_ids_json_str ) ? [] : json_decode($parent_variations_ids_json_str, true);
		$var_total = count($parent_variations_ids);

		if ( empty( $parent_variations_ids ) ) {
			echo "No products variations found.\n";
		}

		foreach ($parent_variations_ids as $var_idx => $var_id) {
			$var_cnt = $var_idx + 1;
			echo "[$var_cnt/$var_total] Processing variation id: $var_id\n";
			$var_id_esc = escapeshellarg($var_id);
			$cmd = "wp wc product_variation update $product_id_esc $var_id_esc --stock_quantity=0 $cmd_suff";
			echo "CMD: [$cmd]\n";
			echo trim(`$cmd`) . "\n";
			@ob_flush();
			flush();
		}

		echo "-------------------------------------------------------------------------------------------------\n\n";
		@ob_flush();
		flush();
	}

	echo "Done at " . date('r') . "\n";
} catch (Exception $e) {
	echo $e->getMessage();
	exit(255);
}

echo "</pre>";

exit(0);

//wp wc product_variation list 69 --user=1
//
//
//wp wc product update 72 --stock_quantity=1 --user=1
//wp wc product update 72 --manage_stock=1 --stock_quantity=7 --user=1
//
//
//
//wp wc product_variation update 72 --stock_quantity=0
//
//
//	wp wc product update 72 --stock_quantity=0 --user=1
//
//