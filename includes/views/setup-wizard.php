<?php
/**
 * Setup Wizard HTML
 * 
 * @var string $org_name
 * @var string $org_email
 * @var string $org_phone
 * @var string $org_description
 * @var array $org_address
 * @var array $org_social
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Welcome to Entity-First SEO!</h1>

    <div class="card" style="max-width: 800px;">
        <h2>Let's set up your organization entity</h2>
        <p>
            This plugin helps search engines understand your business by creating structured data (schema.org markup).
            Let's start by setting up your organization's basic information.
        </p>
        <p>
            <strong>This will:</strong>
        </p>
        <ul>
            <li>✅ Create your organization entity</li>
            <li>✅ Map your homepage to the organization</li>
            <li>✅ Generate proper schema.org markup</li>
            <li>✅ Improve your SEO and Knowledge Graph presence</li>
        </ul>
    </div>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="max-width: 800px;">
        <input type="hidden" name="action" value="ns_entity_run_setup">
        <?php wp_nonce_field('ns_entity_run_setup'); ?>

        <h2>Organization Details</h2>
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
                    </td>
                </tr>
            </tbody>
        </table>

        <details style="margin: 20px 0;">
            <summary
                style="cursor: pointer; font-weight: bold; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                📍 Address (Optional) - Click to expand
            </summary>
            <table class="form-table" role="presentation" style="margin-top: 10px;">
                <tbody>
                    <tr>
                        <th scope="row"><label for="org_address_street">Street Address</label></th>
                        <td><input type="text" name="org_address_street" id="org_address_street"
                                value="<?php echo esc_attr($org_address['street']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="org_address_city">City</label></th>
                        <td><input type="text" name="org_address_city" id="org_address_city"
                                value="<?php echo esc_attr($org_address['city']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="org_address_state">State / Region</label></th>
                        <td><input type="text" name="org_address_state" id="org_address_state"
                                value="<?php echo esc_attr($org_address['state']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="org_address_zip">Zip / Postal Code</label></th>
                        <td><input type="text" name="org_address_zip" id="org_address_zip"
                                value="<?php echo esc_attr($org_address['zip']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="org_address_country">Country</label></th>
                        <td>
                            <input type="text" name="org_address_country" id="org_address_country"
                                value="<?php echo esc_attr($org_address['country']); ?>" class="regular-text"
                                placeholder="US">
                            <p class="description">Two-letter country code</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </details>

        <details style="margin: 20px 0;">
            <summary
                style="cursor: pointer; font-weight: bold; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                🔗 Social Profiles (Optional) - Click to expand
            </summary>
            <table class="form-table" role="presentation" style="margin-top: 10px;">
                <tbody>
                    <tr>
                        <th scope="row"><label for="org_social_linkedin">LinkedIn</label></th>
                        <td><input type="url" name="org_social_linkedin" id="org_social_linkedin"
                                value="<?php echo esc_attr($org_social['linkedin']); ?>" class="regular-text"
                                placeholder="https://www.linkedin.com/company/your-company"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="org_social_facebook">Facebook</label></th>
                        <td><input type="url" name="org_social_facebook" id="org_social_facebook"
                                value="<?php echo esc_attr($org_social['facebook']); ?>" class="regular-text"
                                placeholder="https://www.facebook.com/yourcompany"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="org_social_twitter">Twitter / X</label></th>
                        <td><input type="url" name="org_social_twitter" id="org_social_twitter"
                                value="<?php echo esc_attr($org_social['twitter']); ?>" class="regular-text"
                                placeholder="https://twitter.com/yourcompany"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="org_social_instagram">Instagram</label></th>
                        <td><input type="url" name="org_social_instagram" id="org_social_instagram"
                                value="<?php echo esc_attr($org_social['instagram']); ?>" class="regular-text"
                                placeholder="https://www.instagram.com/yourcompany"></td>
                    </tr>
                </tbody>
            </table>
        </details>

        <p class="submit">
            <?php submit_button('Create Organization Entity', 'primary', 'submit', false); ?>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>"
            style="display: inline; margin-left: 10px;">
            <input type="hidden" name="action" value="ns_entity_skip_setup">
            <?php wp_nonce_field('ns_entity_skip_setup'); ?>
            <button type="submit" class="button">Skip Setup</button>
        </form>
        </p>
    </form>
</div>

<style>
    .required {
        color: #d63638;
    }

    details summary:hover {
        background: #e0e0e1;
    }
</style>