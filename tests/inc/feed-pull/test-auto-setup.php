<?php

/**
 * If everything is working correctly, 
 * you should be able to create link posts with the "import" term 
 * and have feed posts automatically created, 
 * and have the feed posts automatically deleted when the "import" term is removed.
 *
 * Here are the steps to test the functionality:
 * 
 * 1. Create a link post with the "import" term in the "utility" taxonomy. 
 *    This should create a feed post with the link post as its parent.
 *
 * 2. Edit the link post and make sure 
 *    that the feed post is not deleted 
 *    when the "import" term is still assigned. 
 *    Also, make sure that the feed post is deleted when the "import" term is removed.
 *
 * 3. Trash the feed post and make sure that it's deleted.
 * 
 * 4. Add the "import" term to an existing link post 
 *    and make sure that it creates a feed post.
 * 
 * 5. Remove the "import" term from an existing link post 
 *    and make sure that it deletes the feed post.
 */

class AutoSetupTest extends WP_UnitTestCase {
    public function setUp() {
        parent::setUp();

        // Set up test data.
        $this->link_post = $this->factory->post->create_and_get(array(
            'post_type' => 'link',
            'post_title' => 'Test Link Post',
        ));

        $this->feed_post = $this->factory->post->create_and_get(array(
            'post_type' => 'feed',
            'post_title' => 'Test Feed Post',
            'post_parent' => $this->link_post->ID,
        ));

        $this->utility_term = $this->factory->term->create(array(
            'taxonomy' => 'utility',
            'name' => 'Test Utility Term',
        ));
    }

    public function tearDown() {
        parent::tearDown();

        // Clean up test data.
        wp_delete_post($this->link_post->ID, true);
        wp_delete_post($this->feed_post->ID, true);
        wp_delete_term($this->utility_term, 'utility');
    }

    /**
     * Test that a feed post is created when a link post with the "import" term is created.
     */
    public function test_create_feed_post_on_link_post_save_with_import_term() {
        // Add "import" term to link post.
        wp_set_post_terms($this->link_post->ID, $this->utility_term, 'utility');

        // Check that a feed post was created with the link post as parent.
        $feed_post = get_posts(array(
            'post_type' => 'feed',
            'post_parent' => $this->link_post->ID,
        ));
        $this->assertCount(1, $feed_post);
    }

    /**
     * Test that a feed post is not created when a link post without the "import" term is created.
     */
    public function test_create_feed_post_on_link_post_save_without_import_term() {
        // Remove "import" term from link post.
        wp_remove_object_terms($this->link_post->ID, $this->utility_term, 'utility');

        // Check that a feed post was not created.
        $feed_post = get_posts(array(
            'post_type' => 'feed',
            'post_parent' => $this->link_post->ID,
        ));
        $this->assertEmpty($feed_post);
    }

    /**
     * Test that a feed post is deleted when the "import" term is removed from a link post.
     */
    public function test_delete_feed_post_on_link_post_update() {
        // Remove "import" term from link post.
        wp_remove_object_terms($this->link_post->ID, $this->utility_term, 'utility');

        // Check that the feed post was deleted.
        $feed_post = get_post($this->feed_post->ID);
        $this->assertNull($feed_post);
    }

    /**
     * Test that a feed post is not deleted when the "import" term is still assigned to a link post.
     */
    public function test_no_delete_feed_post_on_link_post_update_with_import_term() {
        // Add "import" term to link post.
        wp_set_post_terms($this->link_post->ID, $this->utility_term, 'utility');

        // Check that the feed post was not deleted.
        $feed_post = get_post($this->feed_post->ID);
        $this->assertNotNull($feed_post);
    }

    /**
     * Test that a feed post is deleted when it is trashed.
     */
    public function test_delete_feed_post_on_feed() {
    	// ...
    }
