<form method="POST">
    <input type="text" name="title" required>
    <textarea name="description" required></textarea>
    <select name="category_id" required>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="starting_price" required>
    <input type="datetime-local" name="end_time" required>
    <button type="submit">Create</button>
</form>