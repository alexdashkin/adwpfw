<input type="hidden" name="<?= $name ?>" value="">

<select class="adwpfw-select2 <?= $classes ?>"
        id="<?= $id ?>"
        name="<?= $name ?>"
        <?= $multiple ?> <?= $required ?>
        data-ajax-action="<?= $ajax_action ?>"
        data-placeholder="<?= $placeholder ?>"
        data-min-chars="<?= $min_chars ?>"
        data-min-items-for-search="<?= $min_items_for_search ?>">

    <?php foreach ($options as $option): ?>
        <option value="<?= $option['value'] ?>" <?= $option['selected'] ?>><?= $option['label'] ?></option>
    <?php endforeach; ?>

</select>