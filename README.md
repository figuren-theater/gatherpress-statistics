# GatherPress Statistics

Stable tag: 0.1.0  
Tested up to: 6.8  
License: GPL v2 or later  
Tags: block, gatherpress, events, statistics  
Contributors: carstenbach, WordPress Telex

Display dynamically calculated statistics about your GatherPress events with beautiful, cached counters.

## Description

The GatherPress Statistics block is a powerful analytics tool designed for GatherPress event management. It provides statistics about your GatherPress events, venues, and topics with intelligent caching for optimal performance.

### Key Features

* **Multiple Statistic Types:** Display total events, events per topic, events per venue, events by multiple taxonomy terms, total venues, or venues per topic
* **Smart Caching System:** All statistics are calculated on data change using WordPress hooks and cached as transients for lightning-fast frontend performance
* **Dynamic Taxonomy Support:** Automatically works with all taxonomies registered to GatherPress events
* **Multiple Block Styles:** Choose from Counter, Card, or Minimal display styles
* **Theme.json Integration:** Fully compatible with theme.json spacing, typography, and color settings
* **Performance Optimized:** No heavy queries during frontend rendering - all calculations happen in the background
* **Conditional Formatting:** Show different prefix/suffix based on count thresholds
* **Attendee Tracking:** Display total attendees across events or filtered by taxonomy

### Perfect For:

* Event organizers tracking attendance and engagement
* Community managers analyzing event distribution
* Website owners showcasing event statistics
* Marketing teams highlighting venue utilization

### How It Works:

The block uses WordPress hooks to monitor when GatherPress events, venues, or topics are created, updated, or deleted. When changes occur, statistics are recalculated in the background and stored as transients. The frontend simply displays the cached values, ensuring your site remains fast even with thousands of events.

### Behind the Scenes:

When you save an event, update attendee counts, or modify taxonomy terms, the plugin:
1. Clears all cached statistics (instant)
2. Pre-generates ~50-60 common statistics (0.3-1.2 seconds, background)
3. Stores results as WordPress transients (12-hour expiration)
4. Frontend blocks retrieve cached values (0.001 seconds)

## Installation

1. Upload the plugin files to the `/wp-content/plugins/gatherpress-statistics` directory, ~~or install the plugin through the WordPress plugins screen directly.~~
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure GatherPress plugin is installed and activated
4. Add the "GatherPress Statistics" block to any post or page
5. Configure your desired statistic type and filters in the block settings

## Frequently Asked Questions

### Does this require GatherPress to be installed?

Yes, this block is designed to work with the GatherPress event management plugin. It queries GatherPress custom post types and taxonomies.

### How often are statistics updated?

Statistics are updated immediately when events, venues, or topics are created, modified, or deleted. The caching system ensures changes are reflected instantly while maintaining performance.

### Can I display multiple statistics on one page?

Absolutely! Add multiple instances of the block, each configured to show different statistics.

### How long are statistics cached?

By default, statistics are cached for 12 hours. However, the cache is automatically cleared and regenerated whenever relevant data changes, so your statistics are always current.

### Can I customize the appearance?

Yes! The block supports three built-in styles (Counter, Card, Minimal) and fully respects your theme's color, typography, and spacing settings via theme.json. You can also use conditional prefix/suffix formatting based on count thresholds.

### Will this slow down my site?

No! The block is designed with performance as a top priority. All heavy calculations happen in the background when data changes, not during page loads. Frontend rendering is nearly instant using cached values.

### What happens with custom GatherPress taxonomies?

The block automatically detects and works with any taxonomies registered to the `gatherpress_event` post type. You don't need to modify any code when adding new taxonomies.

## Screenshots

1. Block editor interface showing statistic type selection and filtering options
2. Counter style displaying total events with custom colors
3. Card style showing events per topic with elegant design
4. Minimal style integrated seamlessly into content
5. Inspector controls for configuring statistics and filters
6. Multiple taxonomy filter panel for complex queries
7. Conditional prefix/suffix settings for dynamic formatting

## Changelog

###  0.1.0
* Initial release
* Support for all major GatherPress statistics types
* Smart caching system with automatic invalidation
* Three display style variations
* Full theme.json integration
* Comprehensive taxonomy filtering
* Attendee count statistics
* Conditional prefix/suffix formatting
* Dynamic taxonomy support
* Comprehensive documentation

## Developer Documentation

### Architecture Overview:

The plugin uses a layered architecture for optimal performance:

1. **Data Layer:** WordPress hooks monitor all relevant data changes
2. **Calculation Layer:** Efficient WP_Query-based calculations
3. **Cache Layer:** WordPress transients store results
4. **Presentation Layer:** Block renders cached data

### Example Data Structure (15 events, 3 topics, 3 venues):

Setup:
- Events: E1-E15 (all published)
- Topics: Technology (ID: 1), Workshop (ID: 2), Networking (ID: 3)
- Venues: Downtown Hall (ID: 4), Tech Center (ID: 5), Community Space (ID: 6)
- Attendees: Varying from 10-50 per event

Event Distribution:
- E1, E2, E6, E9, E12: Technology, Downtown Hall (100 total attendees)
- E3, E7, E10: Technology, Tech Center (75 total attendees)
- E4, E8, E13: Workshop, Tech Center (60 total attendees)
- E5, E11, E14: Workshop, Community Space (80 total attendees)
- E15: Networking, Community Space (40 total attendees)

Cached Statistics Examples:

```php
// Total counts
get_transient( 'gatherpress_stats_total_events' ); // Returns: 15
get_transient( 'gatherpress_stats_total_attendees' ); // Returns: 355

// Per taxonomy
get_transient( 'gatherpress_stats_total_taxonomy_terms_abc123' ); // 3 topics
get_transient( 'gatherpress_stats_total_taxonomy_terms_def456' ); // 3 venues

// Per term
get_transient( 'gatherpress_stats_events_per_taxonomy_xyz789' ); // 5 (Technology events)
get_transient( 'gatherpress_stats_total_attendees_xyz789' ); // 175 (Technology attendees)

// Cross-taxonomy
get_transient( 'gatherpress_stats_taxonomy_terms_by_taxonomy_abc123' ); // 2 (venues with Tech events)
```

### Hook Reference:

```php
/**
 * Filter to exclude specific taxonomies from statistics.
 *
 * This filter allows developers to exclude taxonomies from both
 * statistics generation and block editor selection.
 *
 * @since 0.1.0
 *
 * @param array $excluded_taxonomies Array of taxonomy slugs to exclude.
 * @param bool  $for_editor         Whether this is for editor selection.
 * @return array Modified array of excluded taxonomy slugs.
 */
add_filter( 'gatherpress_statistics_excluded_taxonomies', function( $excluded, $for_editor ) {
    // Exclude custom taxonomies from statistics
    $excluded[] = 'post_tag';
    $excluded[] = 'custom_event_type';
    return $excluded;
}, 10, 2 );

/**
 * Filter calculated statistics before caching.
 *
 * Available filters for each statistic type:
 * - gatherpress_stats_calculate_total_events
 * - gatherpress_stats_calculate_events_per_taxonomy
 * - gatherpress_stats_calculate_events_multi_taxonomy
 * - gatherpress_stats_calculate_total_taxonomy_terms
 * - gatherpress_stats_calculate_taxonomy_terms_by_taxonomy
 * - gatherpress_stats_calculate_total_attendees
 *
 * @since 0.1.0
 *
 * @param int   $count   The calculated statistic value.
 * @param array $filters The filters applied to this statistic.
 * @return int Modified statistic value.
 */
add_filter( 'gatherpress_stats_calculate_total_events', function( $count, $filters ) {
    // Modify the calculated event count
    return $count;
}, 10, 2 );
```

### Performance Metrics:

- Cache hit: ~0.001 seconds
- Cache miss + calculation: ~0.005-0.02 seconds per statistic
- Full cache regeneration (50-60 stats): ~0.3-1.2 seconds
- Database impact: Minimal (uses transients, cleared automatically)

### Cache Invalidation Triggers:

1. `save_post` with `post_type=gatherpress_event`
2. `delete_post` with `post_type=gatherpress_event`
3. `updated_post_meta` with `meta_key=gatherpress_attendees_count`
4. `added_post_meta` with `meta_key=gatherpress_attendees_count`
5. `deleted_post_meta` with `meta_key=gatherpress_attendees_count`
6. `create_term`, `edit_term`, `delete_term` for GatherPress taxonomies
7. `set_object_terms` for GatherPress events

## Privacy & Data

This plugin does not collect, store, or transmit any personal data. It only queries and caches statistics about your GatherPress events and taxonomies.