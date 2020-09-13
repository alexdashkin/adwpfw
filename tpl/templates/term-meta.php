<div class="<?= $prefix ?> adwpfw adwpfw-term-meta">

    <?php if ($heading): ?>
        <h2><?= $heading ?></h2>
    <?php endif; ?>

    <table class="form-table">

        <?php foreach ($fields as $field): ?>

            <tr>

                <?php if ($field['label']): ?>

                    <th scope="row">
                        <label for="<?= $field['id'] ?>"><?= $field['label'] ?></label>
                    </th>

                <?php endif; ?>

                <td <?= $field['label'] ? 'colspan="2" style="padding:0"' : '' ?>>

                    <?= $field['content'] ?>

                    <?php if ($field['desc']): ?>
                        <p class="description"><?= $field['desc'] ?></p>
                    <?php endif; ?>

                </td>

            </tr>

        <?php endforeach; ?>

    </table>

</div>