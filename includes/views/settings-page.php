<?php
/**
 * Settings Page HTML
 * 
 * @var string $org_name
 * @var string $org_email
 * @var string $org_phone
 * @var string $org_description
 * @var array $org_address
 * @var array $org_social
 * @var int|null $org_entity_id
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>

    <?php if (isset($_GET['settings-updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Settings saved successfully!</strong> Your organization entity has been updated.</p>
        </div>
    <?php endif; ?>

    <?php if ($org_entity_id): ?>
        <div class="notice notice-info">
            <p>
                <strong>Organization Entity:</strong>
                <a href="<?php echo get_edit_post_link($org_entity_id); ?>" target="_blank">
                    Edit Entity Post
                </a>
                |
                <a href="<?php echo get_permalink($org_entity_id); ?>" target="_blank">
                    View Entity
                </a>
            </p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="ns_entity_save_settings">
        <?php wp_nonce_field('ns_entity_save_settings'); ?>

        <table class="form-table" role="presentation">
            <tbody>
                <!-- Company Name -->
                <tr>
                    <th scope="row">
                        <label for="org_name">Company Name <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" name="org_name" id="org_name" value="<?php echo esc_attr($org_name); ?>"
                            class="regular-text" required>
                        <p class="description">Your organization's name</p>
                    </td>
                </tr>

                <!-- Description -->
                <tr>
                    <th scope="row">
                        <label for="org_description">Description</label>
                    </th>
                    <td>
                        <textarea name="org_description" id="org_description" rows="3"
                            class="large-text"><?php echo esc_textarea($org_description); ?></textarea>
                        <p class="description">Brief description of your organization</p>
                    </td>
                </tr>

                <!-- Email -->
                <tr>
                    <th scope="row">
                        <label for="org_email">Email</label>
                    </th>
                    <td>
                        <input type="email" name="org_email" id="org_email" value="<?php echo esc_attr($org_email); ?>"
                            class="regular-text">
                        <p class="description">Primary contact email</p>
                    </td>
                </tr>

                <!-- Phone -->
                <tr>
                    <th scope="row">
                        <label for="org_phone">Phone</label>
                    </th>
                    <td>
                        <input type="tel" name="org_phone" id="org_phone" value="<?php echo esc_attr($org_phone); ?>"
                            class="regular-text" placeholder="+1-555-123-4567">
                        <p class="description">Primary contact phone number</p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2>Address (Optional)</h2>
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Street Address -->
                <tr>
                    <th scope="row">
                        <label for="org_address_street">Street Address</label>
                    </th>
                    <td>
                        <input type="text" name="org_address_street" id="org_address_street"
                            value="<?php echo esc_attr($org_address['street']); ?>" class="regular-text">
                    </td>
                </tr>

                <!-- City -->
                <tr>
                    <th scope="row">
                        <label for="org_address_city">City</label>
                    </th>
                    <td>
                        <input type="text" name="org_address_city" id="org_address_city"
                            value="<?php echo esc_attr($org_address['city']); ?>" class="regular-text">
                    </td>
                </tr>

                <!-- State / Region -->
                <tr>
                    <th scope="row">
                        <label for="org_address_state">State / Region</label>
                    </th>
                    <td>
                        <input type="text" name="org_address_state" id="org_address_state"
                            value="<?php echo esc_attr($org_address['state']); ?>" class="regular-text">
                    </td>
                </tr>

                <!-- Zip / Postal Code -->
                <tr>
                    <th scope="row">
                        <label for="org_address_zip">Zip / Postal Code</label>
                    </th>
                    <td>
                        <input type="text" name="org_address_zip" id="org_address_zip"
                            value="<?php echo esc_attr($org_address['zip']); ?>" class="regular-text">
                    </td>
                </tr>

                <!-- Country -->
                <tr>
                    <th scope="row">
                        <label for="org_address_country">Country</label>
                    </th>
                    <td>
                        <input type="text" name="org_address_country" id="org_address_country"
                            value="<?php echo esc_attr($org_address['country']); ?>" class="regular-text"
                            placeholder="US">
                        <p class="description">Two-letter country code (e.g., US, GB, CA)</p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2>Social Profiles (Optional)</h2>
        <table class="form-table" role="presentation">
            <tbody>
                <!-- LinkedIn -->
                <tr>
                    <th scope="row">
                        <label for="org_social_linkedin">LinkedIn</label>
                    </th>
                    <td>
                        <input type="url" name="org_social_linkedin" id="org_social_linkedin"
                            value="<?php echo esc_attr($org_social['linkedin']); ?>" class="regular-text"
                            placeholder="https://www.linkedin.com/company/your-company">
                    </td>
                </tr>

                <!-- Facebook -->
                <tr>
                    <th scope="row">
                        <label for="org_social_facebook">Facebook</label>
                    </th>
                    <td>
                        <input type="url" name="org_social_facebook" id="org_social_facebook"
                            value="<?php echo esc_attr($org_social['facebook']); ?>" class="regular-text"
                            placeholder="https://www.facebook.com/yourcompany">
                    </td>
                </tr>

                <!-- Twitter -->
                <tr>
                    <th scope="row">
                        <label for="org_social_twitter">Twitter / X</label>
                    </th>
                    <td>
                        <input type="url" name="org_social_twitter" id="org_social_twitter"
                            value="<?php echo esc_attr($org_social['twitter']); ?>" class="regular-text"
                            placeholder="https://twitter.com/yourcompany">
                    </td>
                </tr>

                <!-- Instagram -->
                <tr>
                    <th scope="row">
                        <label for="org_social_instagram">Instagram</label>
                    </th>
                    <td>
                        <input type="url" name="org_social_instagram" id="org_social_instagram"
                            value="<?php echo esc_attr($org_social['instagram']); ?>" class="regular-text"
                            placeholder="https://www.instagram.com/yourcompany">
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button('Save Settings'); ?>
    </form>

    <hr>

    <h2>About Entity-First SEO</h2>
    <p>
        This plugin helps search engines understand your organization by creating structured data (schema.org markup).
        The settings above are automatically converted into a proper schema.org Organization entity.
    </p>
    <p>
        <strong>Next steps:</strong>
    </p>
    <ul>
        <li>Add team members by creating Person entities</li>
        <li>Add services or products by creating Service/Product entities</li>
        <li>Map entities to pages using the Page Entity Mapping fields</li>
    </ul>
    <p>
        <a href="<?php echo admin_url('edit.php?post_type=ns_entity'); ?>" class="button">
            View All Entities
        </a>
        <a href="<?php echo admin_url('post-new.php?post_type=ns_entity'); ?>" class="button">
            Add New Entity
        </a>
    </p>
</div>

<style>
    .required {
        color: #d63638;
    }
</style>