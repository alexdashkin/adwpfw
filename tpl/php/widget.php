class <?= $id ?> extends WP_Widget
{
    public function __construct()
    {
        parent::__construct('<?= $id ?>', '<?= $name ?>', ['description' => '<?= $description ?>']);
    }

    public function widget($args, $instance)
    {
        do_action('render_<?= $id ?>', $args, $instance, $this);
    }

    public function form($instance)
    {
        do_action('form_<?= $id ?>', $instance, $this);
    }
}
