<?php

function retrieveEntries ($db, $page, $url=NULL)
{
	// If an entry ID was supplied, load the associated entry
	if(isset($url))
	{
		// Load the specific entry
		$sql = "SELECT id, page, title, image, entry, created
				FROM entries
				WHERE url=?
				LIMIT 1";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($url));

		// Save the returned entry array
		$e = $stmt->fetch();

		// Set for full display
		$fulldisp = 1;
	}

	// Otherwise, load all entries
	else
	{
		$sql = "SELECT id, page, title, image, entry, url, created
				FROM entries
				WHERE page=?
				ORDER BY created DESC";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($page));

		$e = NULL; // Declare null to avoid errors.

		// Loop through all entries
		while($row = $stmt->fetch()){
			if($page == "blog")
			{
				$e[] = $row;
				$fulldisp = 0;
			}
			else
			{
				$e[] = $row;
				$fulldisp = 1;
			}
		}

		// If no entry is returned, send a full message
		if(!is_array($e))
		{
			$fulldisp = 1;
			$e = array (
				'title' => 'No Entries Yet!',
				'entry' => '<a href="/simple_blog/admin/">Post an Entry!</a>'
				);
		}
	}

	// Return loaded data
	array_push($e, $fulldisp);

	return $e;
}

function deleteImage($db, $url)
{
	// Select the image from the databes:
	$sql = "SELECT image
			FROM entries
			WHERE url=?
			LIMIT 1";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($url));
	$e = $stmt->fetch();
	$img = $e['image'];
	$imgPath = $_SERVER['DOCUMENT_ROOT'] . $img;

	// Check to see if there is an image:
	if(isset($img))
	{
		// Delete the Image:
		unlink($imgPath);
	}
}

function searchEntries($db, $query)
{
	$sql = "SELECT id, page, title, image, entry, url, created
			FROM entries
			WHERE entry LIKE ?
			ORDER BY created DESC";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($query));

	// Loop through all entries
	while($row = $stmt->fetch())
	{
		$e[] = $row;
		$fulldisp = 0;
	}
	// Return loaded data
	array_push($e, $fulldisp);
	return $e;
}

function deleteEntry($db, $url)
{
	// Delete any Images first:
	deleteImage($db, $url);

	// Then Delete the Post:
	$sql = "DELETE FROM entries
			WHERE url=?
			LIMIT 1";
	$stmt = $db->prepare($sql);
	return $stmt->execute(array($url));
}

function adminLinks ($page, $url)
{
	// Format the link to be followed for each option
	$editURL = "/simple_blog/admin/$page/$url";
	$deleteURL = "/simple_blog/admin/delete/$url";
	
	// Make a hyperlink and add it to an array
	$admin['edit'] = "<a href=\"$editURL\">Edit</a>";
	$admin['delete'] = "<a href=\"$deleteURL\">Delete</a>";

	return $admin;
}

function confirmDelete ($db, $url)
{
	$e = retrieveEntries($db, '', $url);

	return <<<FORM
<form action = "/simple_blog/admin.php" method = "post">
	<fieldset>
		<legend>Are you Sure?</legend>
		<p>Are you sure you want to delete the entry "$e[title]"?</p>
		<input type = "submit" name = "submit" value = "Yes" />
		<input type = "submit" name = "submit" value = "No" />
		<input type = "hidden" name = "action" value = "delete" />
		<input type = "hidden" name = "url" value = "$url" />
	</fieldset>
</form>
FORM;
}

function sanitizeData ($data)
{
	//If data is not an array, run strip_tags()
	if(!is_array($data))
	{
		// Remove all tags except <a> tags
		return strip_tags($data, "<a>");
	}

	// If $data is an array, process each element
	else
	{
		return array_map('sanitizeData', $data);
	}
}

function sanitizeComment($input)
{
	return htmlentities(strip_tags($input), ENT_QUOTES);
}

function makeURL ($title)
{
	$patterns = array(
		'/\s+/',
		'/(?!-)\W+/'
	);
	$replacements = array( '-', '');
	return preg_replace($patterns, $replacements, strtolower($title));
}

function createUserForm()
{
	return <<<FORM
<form action="/simple_blog/inc/update.inc.php" method="post">
	<fieldset>
		<legend>Create New Administrator</legend>
			<label>Username<input type="text" name="username" maxlength="75" /></label>
			<label>Password<input type="password" name="password" /></label>
			<input type="submit" name="submit" value="Create" />
			<input type="submit" name="submit" value="Cancel" />
			<input type="hidden" name="action" value="createuser" />
	</fieldset>
</form>
FORM;
}