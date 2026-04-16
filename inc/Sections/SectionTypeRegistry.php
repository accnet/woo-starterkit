<?php
/**
 * Registry for reusable section definitions.
 *
 * @package StarterKit
 */

namespace StarterKit\Sections;

class SectionTypeRegistry {
	/**
	 * Registered section types.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected $types;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->types = array(
			'hero'         => $this->hero_type(),
			'cta'          => $this->cta_type(),
			'features'     => $this->features_type(),
			'benefits'     => $this->benefits_type(),
			'testimonials' => $this->testimonials_type(),
			'faq'          => $this->faq_type(),
			'banner'       => $this->banner_type(),
			'product_grid' => $this->product_grid_type(),
			'image_text'   => $this->image_text_type(),
			'newsletter'   => $this->newsletter_type(),
		);
	}

	/**
	 * Return all section types.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function all() {
		return $this->types;
	}

	/**
	 * Get one section type.
	 *
	 * @param string $id Section type identifier.
	 * @return array<string, mixed>|null
	 */
	public function get( $id ) {
		return isset( $this->types[ $id ] ) ? $this->types[ $id ] : null;
	}

	/**
	 * Get allowed slots for a section type.
	 *
	 * @param string $id Section type identifier.
	 * @return array<int, string>
	 */
	public function allowed_slots( $id ) {
		$type = $this->get( $id );

		return isset( $type['allowed_slots'] ) && is_array( $type['allowed_slots'] ) ? $type['allowed_slots'] : array();
	}

	/**
	 * Hero section type.
	 *
	 * @return array<string, mixed>
	 */
	protected function hero_type() {
		return array(
			'id'            => 'hero',
			'label'         => __( 'Hero', 'starterkit' ),
			'template'      => 'template-parts/sections/hero/section.php',
			'asset_base'    => 'template-parts/sections/hero',
			'allowed_slots' => array( 'home_after_header', 'home_before_content' ),
			'fields'        => array(
				array( 'name' => 'eyebrow', 'label' => __( 'Eyebrow', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'heading', 'label' => __( 'Heading', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'subheading', 'label' => __( 'Subheading', 'starterkit' ), 'type' => 'textarea' ),
				array( 'name' => 'primary_button_text', 'label' => __( 'Primary Button Text', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'primary_button_url', 'label' => __( 'Primary Button URL', 'starterkit' ), 'type' => 'url' ),
				array( 'name' => 'secondary_button_text', 'label' => __( 'Secondary Button Text', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'secondary_button_url', 'label' => __( 'Secondary Button URL', 'starterkit' ), 'type' => 'url' ),
				array( 'name' => 'background_image_id', 'label' => __( 'Background Image', 'starterkit' ), 'type' => 'media' ),
				array(
					'name'    => 'alignment',
					'label'   => __( 'Alignment', 'starterkit' ),
					'type'    => 'select',
					'options' => array(
						'left'   => __( 'Left', 'starterkit' ),
						'center' => __( 'Center', 'starterkit' ),
					),
				),
			),
			'style_fields'  => array(
				array( 'name' => 'background_color', 'label' => __( 'Background Color', 'starterkit' ), 'type' => 'text' ),
			),
			'default_content' => array(
				'eyebrow'               => __( 'New Collection', 'starterkit' ),
				'heading'               => __( 'Build a storefront that feels designed, not assembled.', 'starterkit' ),
				'subheading'            => __( 'Preset-driven layouts and reusable sections keep WooCommerce pages tidy and fast to manage.', 'starterkit' ),
				'primary_button_text'   => __( 'Shop Now', 'starterkit' ),
				'primary_button_url'    => '#',
				'secondary_button_text' => __( 'Learn More', 'starterkit' ),
				'secondary_button_url'  => '#',
				'background_image_id'   => 0,
				'alignment'             => 'left',
			),
		);
	}

	/**
	 * CTA type.
	 *
	 * @return array<string, mixed>
	 */
	protected function cta_type() {
		return array(
			'id'            => 'cta',
			'label'         => __( 'Call To Action', 'starterkit' ),
			'template'      => 'template-parts/sections/cta/section.php',
			'asset_base'    => 'template-parts/sections/cta',
			'allowed_slots' => array( 'home_after_content', 'archive_before_loop', 'product_after_summary' ),
			'fields'        => array(
				array( 'name' => 'heading', 'label' => __( 'Heading', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'content', 'label' => __( 'Content', 'starterkit' ), 'type' => 'textarea' ),
				array( 'name' => 'button_text', 'label' => __( 'Button Text', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'button_url', 'label' => __( 'Button URL', 'starterkit' ), 'type' => 'url' ),
				array( 'name' => 'background_image_id', 'label' => __( 'Background Image', 'starterkit' ), 'type' => 'media' ),
			),
			'style_fields'  => array(
				array(
					'name'    => 'tone',
					'label'   => __( 'Tone', 'starterkit' ),
					'type'    => 'select',
					'options' => array(
						'dark'  => __( 'Dark', 'starterkit' ),
						'light' => __( 'Light', 'starterkit' ),
					),
				),
			),
			'default_content' => array(
				'heading'             => __( 'Ready to launch your next campaign?', 'starterkit' ),
				'content'             => __( 'Promote a category, collection, or seasonal push without rebuilding templates.', 'starterkit' ),
				'button_text'         => __( 'Explore Offers', 'starterkit' ),
				'button_url'          => '#',
				'background_image_id' => 0,
			),
		);
	}

	/**
	 * Features type.
	 *
	 * @return array<string, mixed>
	 */
	protected function features_type() {
		return array(
			'id'            => 'features',
			'label'         => __( 'Features', 'starterkit' ),
			'template'      => 'template-parts/sections/features/section.php',
			'asset_base'    => 'template-parts/sections/features',
			'allowed_slots' => array( 'home_before_content', 'archive_after_title' ),
			'fields'        => array(
				array( 'name' => 'heading', 'label' => __( 'Heading', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'intro', 'label' => __( 'Intro', 'starterkit' ), 'type' => 'textarea' ),
				array(
					'name'       => 'items',
					'label'      => __( 'Items', 'starterkit' ),
					'type'       => 'list',
					'min_rows'   => 2,
					'item_fields'=> array(
						array( 'name' => 'icon', 'label' => __( 'Icon Label', 'starterkit' ), 'type' => 'text' ),
						array( 'name' => 'title', 'label' => __( 'Title', 'starterkit' ), 'type' => 'text' ),
						array( 'name' => 'description', 'label' => __( 'Description', 'starterkit' ), 'type' => 'textarea' ),
					),
				),
			),
			'style_fields'  => array(
				array(
					'name'    => 'columns',
					'label'   => __( 'Columns', 'starterkit' ),
					'type'    => 'select',
					'options' => array( '2' => '2', '3' => '3' ),
				),
			),
			'default_content' => array(
				'heading' => __( 'Why teams choose this setup', 'starterkit' ),
				'intro'   => __( 'Curated controls keep the storefront flexible while protecting layout quality.', 'starterkit' ),
				'items'   => array(
					array(
						'icon'        => 'layout',
						'title'       => __( 'Preset Layouts', 'starterkit' ),
						'description' => __( 'Switch header, footer, archive, and product structures without rewriting templates.', 'starterkit' ),
					),
					array(
						'icon'        => 'layers',
						'title'       => __( 'Slot Injection', 'starterkit' ),
						'description' => __( 'Reusable sections appear only where the layout allows them.', 'starterkit' ),
					),
				),
			),
		);
	}

	/**
	 * Benefits type.
	 *
	 * @return array<string, mixed>
	 */
	protected function benefits_type() {
		return array(
			'id'            => 'benefits',
			'label'         => __( 'Benefits', 'starterkit' ),
			'template'      => 'template-parts/sections/benefits/section.php',
			'asset_base'    => 'template-parts/sections/benefits',
			'allowed_slots' => array( 'product_after_summary', 'product_after_tabs' ),
			'fields'        => array(
				array( 'name' => 'heading', 'label' => __( 'Heading', 'starterkit' ), 'type' => 'text' ),
				array(
					'name'       => 'items',
					'label'      => __( 'Items', 'starterkit' ),
					'type'       => 'list',
					'min_rows'   => 2,
					'item_fields'=> array(
						array( 'name' => 'title', 'label' => __( 'Title', 'starterkit' ), 'type' => 'text' ),
						array( 'name' => 'description', 'label' => __( 'Description', 'starterkit' ), 'type' => 'textarea' ),
					),
				),
			),
			'style_fields'  => array(
				array(
					'name'    => 'emphasis',
					'label'   => __( 'Emphasis Style', 'starterkit' ),
					'type'    => 'select',
					'options' => array(
						'soft'   => __( 'Soft', 'starterkit' ),
						'strong' => __( 'Strong', 'starterkit' ),
					),
				),
			),
			'default_content' => array(
				'heading' => __( 'Storefront Benefits', 'starterkit' ),
				'items'   => array(
					array(
						'title'       => __( 'Faster merchandising', 'starterkit' ),
						'description' => __( 'Launch promotional content without changing the product template.', 'starterkit' ),
					),
					array(
						'title'       => __( 'Safer admin controls', 'starterkit' ),
						'description' => __( 'Teams configure content and rules without touching structural HTML.', 'starterkit' ),
					),
				),
			),
		);
	}

	/**
	 * Testimonials type.
	 *
	 * @return array<string, mixed>
	 */
	protected function testimonials_type() {
		return array(
			'id'            => 'testimonials',
			'label'         => __( 'Testimonials', 'starterkit' ),
			'template'      => 'template-parts/sections/testimonials/section.php',
			'asset_base'    => 'template-parts/sections/testimonials',
			'allowed_slots' => array( 'home_after_content', 'product_after_tabs' ),
			'fields'        => array(
				array( 'name' => 'heading', 'label' => __( 'Heading', 'starterkit' ), 'type' => 'text' ),
				array(
					'name'       => 'items',
					'label'      => __( 'Testimonials', 'starterkit' ),
					'type'       => 'list',
					'min_rows'   => 2,
					'item_fields'=> array(
						array( 'name' => 'quote', 'label' => __( 'Quote', 'starterkit' ), 'type' => 'textarea' ),
						array( 'name' => 'author', 'label' => __( 'Author', 'starterkit' ), 'type' => 'text' ),
						array( 'name' => 'role', 'label' => __( 'Role', 'starterkit' ), 'type' => 'text' ),
					),
				),
			),
			'style_fields'  => array(),
			'default_content' => array(
				'heading' => __( 'Loved by merchandising teams', 'starterkit' ),
				'items'   => array(
					array(
						'quote'  => __( 'We can launch landing experiences without handing editors a fragile page builder.', 'starterkit' ),
						'author' => __( 'Mina Tran', 'starterkit' ),
						'role'   => __( 'Commerce Lead', 'starterkit' ),
					),
					array(
						'quote'  => __( 'The slot system keeps campaigns fast without compromising structure.', 'starterkit' ),
						'author' => __( 'Arun Patel', 'starterkit' ),
						'role'   => __( 'Growth Manager', 'starterkit' ),
					),
				),
			),
		);
	}

	/**
	 * FAQ type.
	 *
	 * @return array<string, mixed>
	 */
	protected function faq_type() {
		return array(
			'id'            => 'faq',
			'label'         => __( 'FAQ', 'starterkit' ),
			'template'      => 'template-parts/sections/faq/section.php',
			'asset_base'    => 'template-parts/sections/faq',
			'allowed_slots' => array( 'home_after_content', 'product_after_tabs' ),
			'fields'        => array(
				array( 'name' => 'heading', 'label' => __( 'Heading', 'starterkit' ), 'type' => 'text' ),
				array(
					'name'       => 'items',
					'label'      => __( 'Questions', 'starterkit' ),
					'type'       => 'list',
					'min_rows'   => 2,
					'item_fields'=> array(
						array( 'name' => 'question', 'label' => __( 'Question', 'starterkit' ), 'type' => 'text' ),
						array( 'name' => 'answer', 'label' => __( 'Answer', 'starterkit' ), 'type' => 'textarea' ),
					),
				),
			),
			'style_fields'  => array(),
			'default_content' => array(
				'heading' => __( 'Common questions', 'starterkit' ),
				'items'   => array(
					array(
						'question' => __( 'Can editors break the product template?', 'starterkit' ),
						'answer'   => __( 'No. They can only place reusable sections inside approved slots.', 'starterkit' ),
					),
					array(
						'question' => __( 'Can the same section appear on multiple pages?', 'starterkit' ),
						'answer'   => __( 'Yes. Use include and exclude rules to control where it renders.', 'starterkit' ),
					),
				),
			),
		);
	}

	/**
	 * Banner type.
	 *
	 * @return array<string, mixed>
	 */
	protected function banner_type() {
		return array(
			'id'            => 'banner',
			'label'         => __( 'Promo Banner', 'starterkit' ),
			'template'      => 'template-parts/sections/banner/section.php',
			'asset_base'    => 'template-parts/sections/banner',
			'allowed_slots' => array( 'archive_before_title', 'archive_before_loop', 'home_after_header' ),
			'fields'        => array(
				array( 'name' => 'heading', 'label' => __( 'Heading', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'content', 'label' => __( 'Content', 'starterkit' ), 'type' => 'textarea' ),
				array( 'name' => 'button_text', 'label' => __( 'Button Text', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'button_url', 'label' => __( 'Button URL', 'starterkit' ), 'type' => 'url' ),
			),
			'style_fields'  => array(
				array( 'name' => 'background_color', 'label' => __( 'Background Color', 'starterkit' ), 'type' => 'text' ),
			),
			'default_content' => array(
				'heading'     => __( 'Seasonal Promotion', 'starterkit' ),
				'content'     => __( 'Feature a campaign or collection at the top of a high-traffic page.', 'starterkit' ),
				'button_text' => __( 'View Collection', 'starterkit' ),
				'button_url'  => '#',
			),
		);
	}

	/**
	 * Product grid type.
	 *
	 * @return array<string, mixed>
	 */
	protected function product_grid_type() {
		return array(
			'id'            => 'product_grid',
			'label'         => __( 'Product Grid', 'starterkit' ),
			'template'      => 'template-parts/sections/product-grid/section.php',
			'asset_base'    => 'template-parts/sections/product-grid',
			'allowed_slots' => array( 'home_after_content', 'archive_after_loop' ),
			'fields'        => array(
				array( 'name' => 'heading', 'label' => __( 'Heading', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'limit', 'label' => __( 'Product Limit', 'starterkit' ), 'type' => 'text' ),
			),
			'style_fields'  => array(),
			'default_content' => array(
				'heading' => __( 'Featured Products', 'starterkit' ),
				'limit'   => '4',
			),
		);
	}

	/**
	 * Image and text type.
	 *
	 * @return array<string, mixed>
	 */
	protected function image_text_type() {
		return array(
			'id'            => 'image_text',
			'label'         => __( 'Image + Text', 'starterkit' ),
			'template'      => 'template-parts/sections/image-text/section.php',
			'asset_base'    => 'template-parts/sections/image-text',
			'allowed_slots' => array( 'home_before_content', 'home_after_content' ),
			'fields'        => array(
				array( 'name' => 'heading', 'label' => __( 'Heading', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'content', 'label' => __( 'Content', 'starterkit' ), 'type' => 'textarea' ),
				array( 'name' => 'image_id', 'label' => __( 'Image', 'starterkit' ), 'type' => 'media' ),
				array(
					'name'    => 'layout',
					'label'   => __( 'Layout', 'starterkit' ),
					'type'    => 'select',
					'options' => array(
						'image-left'  => __( 'Image Left', 'starterkit' ),
						'image-right' => __( 'Image Right', 'starterkit' ),
					),
				),
			),
			'style_fields'  => array(),
			'default_content' => array(
				'heading' => __( 'Tell a sharper brand story', 'starterkit' ),
				'content' => __( 'Pair a strong visual with a concise explanation, campaign note, or merchandising message.', 'starterkit' ),
				'image_id'=> 0,
				'layout'  => 'image-left',
			),
		);
	}

	/**
	 * Newsletter type.
	 *
	 * @return array<string, mixed>
	 */
	protected function newsletter_type() {
		return array(
			'id'            => 'newsletter',
			'label'         => __( 'Newsletter', 'starterkit' ),
			'template'      => 'template-parts/sections/newsletter/section.php',
			'asset_base'    => 'template-parts/sections/newsletter',
			'allowed_slots' => array( 'home_before_footer', 'footer_top' ),
			'fields'        => array(
				array( 'name' => 'heading', 'label' => __( 'Heading', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'content', 'label' => __( 'Content', 'starterkit' ), 'type' => 'textarea' ),
				array( 'name' => 'placeholder', 'label' => __( 'Input Placeholder', 'starterkit' ), 'type' => 'text' ),
				array( 'name' => 'button_text', 'label' => __( 'Button Text', 'starterkit' ), 'type' => 'text' ),
			),
			'style_fields'  => array(),
			'default_content' => array(
				'heading'     => __( 'Stay in the loop', 'starterkit' ),
				'content'     => __( 'Promote your newsletter or CRM form in a reusable section.', 'starterkit' ),
				'placeholder' => __( 'Email address', 'starterkit' ),
				'button_text' => __( 'Subscribe', 'starterkit' ),
			),
		);
	}
}
