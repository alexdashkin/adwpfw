<div id="<?= $id ?>" class="<?= $controlClasses ?>">

    <select>

        <?php foreach ($options as $option): ?>

            <option value="<?= $option['value'] ?>" <?= $option['value'] == $value ? 'selected' : '' ?>>
                <?= $option['label'] ?>
            </option>

        <?php endforeach; ?>

    </select>

    <button class="button">Do</button>

</div>
