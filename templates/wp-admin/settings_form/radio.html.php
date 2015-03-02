<tr valign="top">
	<th scope="row">
		<label for="<?php echo $key; ?>"><?php echo $name; ?></label>
	</th>
	<td>
		<?php foreach ($options as $index => $option): ?>
			<label for="<?php echo "{$key}_{$index}"; ?>">
				<input type="radio" name="<?php echo $key; ?>" id="<?php echo "{$key}_{$index}"; ?>" value="<?php echo isset($option['value']) ? $option['value'] : ""; ?>" class="<?php echo isset($option['class']) ? $option['class'] : ""; ?>" <?php echo $value == (isset($option['value']) ? $option['value'] : "") ? 'checked' : ''; ?> /> <?php echo isset($option['label']) ? $option['label'] : ""; ?>
			</label>

			<br />
		<?php endforeach; ?>

		<?php if (isset($description)): ?>
			<p class="description"><?php echo $description; ?></p>
		<?php endif; ?>
	</td>
</tr>