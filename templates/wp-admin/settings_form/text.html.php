<tr valign="top">
	<th scope="row">
		<label for="<?php echo $key; ?>"><?php echo $name; ?></label>
	</th>
	<td>
		<input type="<?php echo !$type ? 'text' : $type; ?>" name="<?php echo $key; ?>" id="<?php echo $key; ?>" value="<?php echo $value; ?>" class="<?php echo !$class ? 'regular-text' : $class; ?>" <?php echo isset($checked) ? $checked : ''; ?> />
		<?php if (isset($description) && !empty($description)): ?>
			<p class="description"><?php echo $description; ?></p>
		<?php endif; ?>
	</td>
</tr>