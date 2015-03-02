<tr valign="top">
	<th scope="row">
		<label for="<?php echo $key ?: ""; ?>"><?php echo $name ?: ""; ?></label>
	</th>
	<td>
		<textarea name="<?php echo $key ?: ""; ?>" id="<?php echo $key ?: ""; ?>" class="<?php echo isset($class) ? $class : "regular-text"; ?>" rows="3" cols="15" /><?php echo isset($value) ? $value : ""; ?></textarea>

		<?php if (isset($description) && !empty($description)): ?>
			<p class="description"><?php echo $description; ?></p>
		<?php endif; ?>
	</td>
</tr>