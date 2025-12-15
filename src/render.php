<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$statistic_type = isset( $attributes['statisticType'] ) ? $attributes['statisticType'] : 'total_events';
$label_singular = isset( $attributes['labelSingular'] ) ? $attributes['labelSingular'] : __( 'Event', 'gatherpress-statistics' );
$label_plural   = isset( $attributes['labelPlural'] ) ? $attributes['labelPlural'] : __( 'Events', 'gatherpress-statistics' );
$selected_term  = isset( $attributes['selectedTerm'] ) ? intval( $attributes['selectedTerm'] ) : 0;
$event_query    = isset( $attributes['eventQuery'] ) ? $attributes['eventQuery'] : 'past';
$show_label     = isset( $attributes['showLabel'] ) ? $attributes['showLabel'] : true;

// Prefix and suffix settings
$prefix_default      = isset( $attributes['prefixDefault'] ) ? $attributes['prefixDefault'] : '';
$suffix_default      = isset( $attributes['suffixDefault'] ) ? $attributes['suffixDefault'] : '';
$prefix_conditional  = isset( $attributes['prefixConditional'] ) ? $attributes['prefixConditional'] : '';
$suffix_conditional  = isset( $attributes['suffixConditional'] ) ? $attributes['suffixConditional'] : '';
$conditional_threshold = isset( $attributes['conditionalThreshold'] ) ? intval( $attributes['conditionalThreshold'] ) : 10;

// CRITICAL: Validate event_query - only 'upcoming' or 'past' allowed
if ( empty( $event_query ) || ! in_array( $event_query, array( 'upcoming', 'past' ), true ) ) {
	// Default to 'past' if invalid or empty
	$event_query = 'past';
}

// Build filters array
$filters = array();

// CRITICAL: Add event query filter FIRST - this must be present for cache key generation
// Only 'upcoming' or 'past' are valid values
$filters['event_query'] = sanitize_key( $event_query );

if ( $selected_term > 0 ) {
	$filters['term_id'] = $selected_term;
}

// Get selected taxonomy for single taxonomy operations
if ( ! empty( $attributes['selectedTaxonomy'] ) ) {
	$filters['taxonomy'] = $attributes['selectedTaxonomy'];
}

// Get count and filter taxonomies for cross-taxonomy operations
if ( ! empty( $attributes['countTaxonomy'] ) ) {
	$filters['count_taxonomy'] = $attributes['countTaxonomy'];
}

if ( ! empty( $attributes['filterTaxonomy'] ) ) {
	$filters['filter_taxonomy'] = $attributes['filterTaxonomy'];
}

// Handle multiple taxonomy selection
if ( 'events_multi_taxonomy' === $statistic_type ) {
	$taxonomy_terms = array();
	
	if ( ! empty( $attributes['selectedTaxonomyTerms'] ) ) {
		foreach ( $attributes['selectedTaxonomyTerms'] as $taxonomy => $term_ids ) {
			if ( ! empty( $term_ids ) && is_array( $term_ids ) ) {
				$taxonomy_terms[ $taxonomy ] = $term_ids;
			}
		}
	}
	
	if ( ! empty( $taxonomy_terms ) ) {
		$filters['taxonomy_terms'] = $taxonomy_terms;
	}
}

// DEBUG: Generate cache key for inspection
$cache_key = \GatherPressStatistics\get_cache_key( $statistic_type, $filters );

// Get cached statistic
$count = \GatherPressStatistics\get_cached( $statistic_type, $filters );

// DEBUG: Build debug output array
$debug_info = array(
	'cache_key' => $cache_key,
	'cached_value' => get_transient( $cache_key ),
	'calculated_value' => $count,
	'filters' => $filters,
	'event_query' => $event_query,
	'statistic_type' => $statistic_type,
);

// Add all related transients for inspection
$debug_info['all_stats_transients'] = array(
	'upcoming' => get_transient( \GatherPressStatistics\get_cache_key( $statistic_type, array_merge( $filters, array( 'event_query' => 'upcoming' ) ) ) ),
	'past' => get_transient( \GatherPressStatistics\get_cache_key( $statistic_type, array_merge( $filters, array( 'event_query' => 'past' ) ) ) ),
);

// Don't display if count is 0
if ( $count === 0 ) {
	return;
}

// Determine which prefix/suffix to use based on threshold
$use_conditional = $count > $conditional_threshold;
$display_prefix  = ( $use_conditional && ! empty( $prefix_conditional ) ) ? $prefix_conditional : $prefix_default;
$display_suffix  = ( $use_conditional && ! empty( $suffix_conditional ) ) ? $suffix_conditional : $suffix_default;

// Determine which label to use based on count (singular for 1, plural for everything else)
$display_label = ( 1 === $count ) ? $label_singular : $label_plural;

?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<div class="gatherpress-stats-number">
		<?php 
		if ( ! empty( $display_prefix ) ) {
			echo esc_html( $display_prefix ) . ' ';
		}
		echo esc_html( number_format_i18n( $count ) );
		if ( ! empty( $display_suffix ) ) {
			echo ' ' . esc_html( $display_suffix );
		}
		?>
	</div>
	<?php if ( $show_label && ! empty( $display_label ) ) : ?>
		<div class="gatherpress-stats-label">
			<?php echo esc_html( $display_label ); ?>
		</div>
	<?php endif; ?>
	
	<!-- DEBUG OUTPUT: Remove this section after debugging -->
	<details style="margin-top: 1rem; padding: 1rem; background: #f0f0f0; border: 1px solid #ccc; font-size: 12px; font-family: monospace;">
		<summary style="cursor: pointer; font-weight: bold;">🔍 Debug Information (Click to expand)</summary>
		<div style="margin-top: 0.5rem;">
			<h4 style="margin: 0.5rem 0;">Cache Details:</h4>
			<pre style="background: white; padding: 0.5rem; overflow-x: auto;"><?php echo esc_html( print_r( $debug_info, true ) ); ?></pre>
			
			<h4 style="margin: 0.5rem 0;">Quick Reference:</h4>
			<ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
				<li><strong>Cache Key:</strong> <?php echo esc_html( $cache_key ); ?></li>
				<li><strong>Event Query:</strong> <?php echo esc_html( $event_query ); ?></li>
				<li><strong>Cached Value (Upcoming):</strong> <?php echo esc_html( $debug_info['all_stats_transients']['upcoming'] !== false ? $debug_info['all_stats_transients']['upcoming'] : 'Not cached' ); ?></li>
				<li><strong>Cached Value (Past):</strong> <?php echo esc_html( $debug_info['all_stats_transients']['past'] !== false ? $debug_info['all_stats_transients']['past'] : 'Not cached' ); ?></li>
				<li><strong>Displayed Count:</strong> <?php echo esc_html( $count ); ?></li>
			</ul>
			
			<h4 style="margin: 0.5rem 0;">Diagnosis:</h4>
			<ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
				<?php if ( $debug_info['all_stats_transients']['upcoming'] === $debug_info['all_stats_transients']['past'] && $debug_info['all_stats_transients']['upcoming'] !== false ) : ?>
					<li style="color: red; font-weight: bold;">⚠️ WARNING: Upcoming and Past values are identical! Cache key may not include event_query.</li>
				<?php endif; ?>
				
				<?php if ( strpos( $cache_key, $event_query ) === false ) : ?>
					<li style="color: red; font-weight: bold;">⚠️ WARNING: Event query type (<?php echo esc_html( $event_query ); ?>) not found in cache key!</li>
				<?php else : ?>
					<li style="color: green;">✓ Event query type is present in cache key</li>
				<?php endif; ?>
				
				<?php if ( $debug_info['cached_value'] === false ) : ?>
					<li style="color: orange;">⚠️ Cache MISS: Value was calculated fresh</li>
				<?php else : ?>
					<li style="color: green;">✓ Cache HIT: Value retrieved from cache</li>
				<?php endif; ?>
			</ul>
		</div>
	</details>
</div>