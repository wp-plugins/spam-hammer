<tr valign="top">
	<th scope="row">
		<label for="<?php echo $key; ?>"><?php echo $name; ?></label>
	</th>
	<td>
		<?php foreach ($options as $index => $option): ?>
			<label for="<?php echo "{$key}_{$index}"; ?>">
				<input type="radio" name="<?php echo $key; ?>" id="<?php echo "{$key}_{$index}"; ?>" value="<?php echo $option['value']; ?>" class="<?php echo $option['class'] ? $option['class'] : ''; ?>" <?php echo $value == $option['value'] ? 'checked' : ''; ?> /> <?php echo $option['label']; ?>
			</label>

			<br />
		<?php endforeach; ?>

		<?php if (isset($description) && !empty($description)): ?>
			<p class="description"><?php echo $description; ?></p>
		<?php endif; ?>
	</td>
</tr>