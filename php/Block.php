<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$class_name = '';
		$post_types = get_post_types( array( 'public' => true ) );
		if ( isset( $attributes['className'] ) && ! empty( $attributes['className'] ) ) :
			$class_name = $attributes['className'];
		endif;

		ob_start();
		?>
		<div <?php echo $class_name ? 'class="' . esc_attr( $class_name ) . '"' : ''; ?>>
			<h2><?php esc_html_e( 'Post Counts', 'site-counts' ); ?></h2>
			<?php if ( is_array( $post_types ) ) : ?>
			<ul>
			<?php
			foreach ( $post_types as $post_type_slug ) :
				$post_type_object  = get_post_type_object( $post_type_slug  );
				$post_count_object = wp_count_posts( $post_type_slug );

				if ( 'attachment' === $post_type_slug ) :
					$post_count = $post_count_object->inherit;
				else :
					$post_count = $post_count_object->publish;
				endif;
				?>
				<li>
				<?php
				printf(
					/* translators: %1$s: Post count number, %2$s: Post type singular name, %3$s: Post type plural name. */
					esc_html( _nx( 'There is %1$s %2$s.', 'There are %1$s %3$s.', $post_count, 'Post Count List Item', 'site-counts' ) ),
					esc_html( number_format_i18n( $post_count ) ),
					$post_type_object->labels->singular_name,
					$post_type_object->labels->name
				);
				?>
				</li>
			<?php endforeach; // $post_types as $post_type_slug ?>
			</ul>
			<?php endif; // is_array( $post_types ) ?>

			<p>
			<?php printf(
				/* translators: %s: Current Post ID. */
				esc_html__( 'The current post ID is %s.', 'site-counts' ),  get_the_ID()
			); ?>
			</p>

			<?php
			$query = new WP_Query( array(
				'post_type'      => array( 'post', 'page' ),
				'posts_per_page' => 5,
				'post_status'    => 'any',
				'tag'            => 'foo',
				'category_name'  => 'baz',
				'post__not_in'   => array( get_the_ID() ),
				'date_query'     => array(
					array(
						'hour'      => 9,
						'compare'   => '>=',
					),
					array(
						'hour' => 17,
						'compare'=> '<=',
					),
				),
			) );

			if ( $query->have_posts() ) :
			?>
				<h2><?php esc_html_e( 'Any 5 posts with the tag of foo and the category of baz', 'site-counts' ); ?></h2>
				<ul>
				<?php
				while ( $query->have_posts() ) : $query->the_post();
					the_title( '<li>', '</li>' );
				endwhile;
				wp_reset_postdata();
				?>
				</ul>
			<?php endif; // $query->have_posts() ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
