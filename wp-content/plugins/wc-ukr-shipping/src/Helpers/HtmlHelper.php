<?php

namespace kirillbdev\WCUkrShipping\Helpers;

if ( ! defined('ABSPATH')) {
    exit;
}

class HtmlHelper
{
    public static function textField(string $name, string $label, ?string $value, ?string $tooltip = null): void
    {
        $id = self::getIdFromName($name);
        $html = '<div class="wcus-form-group">';
        $html .= sprintf('<label for="%s">%s</label>', esc_attr($id), esc_html($label));
        $html .= sprintf(
            '<input type="text" id="%s" name="%s" class="wcus-form-control" value="%s">',
            esc_attr($id),
            esc_attr($name),
            esc_attr($value)
        );

        if ($tooltip !== null) {
            $html .= sprintf('<div class="wcus-form-group__tooltip">%s</div>', esc_html($tooltip));
        }

        $html .= '</div>';

        echo $html;
    }

	/**
	 * @param string $id
	 * @param array $options
	 */
	public static function selectFieldLegacy($id, $options = [])
	{
        $class = '';

        if (!empty($options['class'])) {
            $class = implode(' ', $options['class']);
        }

        $attributes = '';

        if (!empty($options['attributes'])) {
            foreach ($options['attributes'] as $key => $value) {
                $attributes .= $key . '="' . $value . '"';
            }
        }

        $html = sprintf(
            '<select name="%s" id="%s" class="%s"%s>',
            $id,
            $id,
            $class,
            $attributes
        );

        if (!empty($options['options'])) {
            foreach ($options['options'] as $key => $value) {
                $html .= sprintf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($key),
                    isset($options['value']) && $options['value'] === $key ? ' selected' : '',
                    esc_attr($value)
                );
            }
        }

        $html .= '</select>';

        echo $html;
	}

    public static function selectField(
        string $name,
        string $label,
        array $options,
        ?string $value = null,
        ?string $tooltip = null
    ): void {
        $id = self::getIdFromName($name);

        ?>
        <div class="wcus-form-group">
            <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
            <select name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id); ?>" class="wcus-form-control">
                <?php foreach ($options as $optionValue => $optionName) { ?>
                    <option value="<?php echo esc_attr($optionValue); ?>" <?php echo (string)$optionValue === $value ? 'selected' : ''; ?>>
                        <?php echo esc_html($optionName); ?>
                    </option>
                <?php } ?>
            </select>
            <?php if ($tooltip !== null) { ?>
                <div class="wcus-form-group__tooltip"><?php echo esc_html($tooltip); ?></div>
            <?php } ?>
        </div>
        <?php
    }

    public static function switcherField(string $name, string $label, bool $checked, ?string $tooltip = null, ?string $value = null): void
    {
    ?>
        <div class="wcus-form-group">
            <div class="wcus-form-group--horizontal">
                <label class="wcus-switcher">
                    <?php if ($value === null) { ?>
                        <input type="hidden" name="<?php echo esc_attr($name); ?>" value="0">
                        <input type="checkbox"
                               name="<?php echo esc_attr($name); ?>"
                               value="1" <?php echo $checked ? 'checked' : ''; ?>>
                    <?php } else { ?>
                        <input type="checkbox"
                               name="<?php echo esc_attr($name); ?>"
                               value="<?php echo esc_attr($value); ?>" <?php echo $checked ? 'checked' : ''; ?>>
                    <?php } ?>
                    <span class="wcus-switcher__control"></span>
                </label>
                <div class="wcus-control-label"><?php echo esc_html($label); ?></div>
            </div>
            <?php if ($tooltip !== null) { ?>
                <div class="wcus-form-group__tooltip" style="padding-left: 58px;"><?php echo esc_html($tooltip); ?></div>
            <?php } ?>
        </div>
    <?php
    }

    private static function getIdFromName(string $name): string
    {
        return trim(str_replace(['[', ']'], '_', $name), '_');
    }
}
