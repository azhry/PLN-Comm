<?php 

/**
 * endpoint.php
 * Created and documented by Azhary Arliansyah 28/07/2017
 * REST API endpoint
 */

// define database credentials
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASSWORD", "");
define("DB_DATABASE", "db_pln");

require_once('DBHelper.php');


// connect to database with DBHelper static class
DBHelper::connect(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);

// initialize response to be sent to client
$response['error'] = FALSE;

function sortUser($a, $b){
	$sortName = sortUserByName($a,$b);
	return ($sortName == 0) ? sortUserByEmail($a,$b) : $sortName;
}

function sortUserByName($a, $b)
{
    $a_name = strtolower($a['NAME']);
    $b_name = strtolower($b['NAME']);

    if ($a_name == $b_name) return 0;
    return ($a_name < $b_name) ? -1 : 1;
}

function sortUserByEmail($a, $b)
{
	$a_email = strtolower($a['EMAIL']);
    $b_email = strtolower($b['EMAIL']);

    if ($a_email == $b_email) return 0;
    return ($a_email < $b_email) ? -1 : 1;
}

// evaluate request method coming from client
switch ($_SERVER['REQUEST_METHOD'])
{
	case 'GET':
			
		// evaluate what action is requested by client
		$action = $_GET['action'];
		switch ($action) 
		{
			case 'get_todo_list':
				
				$user_id 	= $_GET['user_id'];
				$todo_list_access = DBHelper::select('list_access', ['*'], [
					'USER_ID' => $user_id
				]);

				$todo_lists = [];
				$idx = 0;
				foreach ($todo_list_access as $list_id)
				{
					$todo_lists []= DBHelper::select_row('todo_lists', ['*'], [
						'LIST_ID'	=> $list_id['LIST_ID']
					]);
					$todo_lists[$idx]['ACCESS_TYPE'] = $list_id['ACCESS_TYPE'];
					$idx++;
				}

				echo json_encode($todo_lists);

				exit;

			case 'get_todo_item':

				$list_id = $_GET['list_id'];

				$todo_items = DBHelper::select('todo_items', ['TODO_ID', 'ITEM_DESC', 'IS_COMPLETED', 'LIST_ID', 'NOTE', 'DUE_DATE'], [
					'LIST_ID'	=> $list_id
				]);

				foreach ($todo_items as $key => $todo_item) 
				{
					$check_files = DBHelper::select('files', ['FILE_ID'], [
						'TODO_ID' => $todo_item['TODO_ID']
					]);
					if (count($check_files) > 0) 
					{
						$todo_item['HAS_FILES'] = TRUE;
					}
					else
					{
						$todo_item['HAS_FILES'] = FALSE;
					}
					$todo_items[$key] = $todo_item;
				}

				echo json_encode($todo_items);

				exit;

			case 'get_item_details':

				$todo_id 	= $_GET['todo_id'];
				$todo_item 	= DBHelper::select_row('todo_items', ['*'], ['TODO_ID' => $todo_id]);
				echo json_encode($todo_item);

				exit;

			case 'get_list_members':

				$list_id 	= $_GET['list_id'];
				$members_id = DBHelper::select('list_access', ['USER_ID', 'ACCESS_TYPE'], ['LIST_ID' => $list_id]);
				$members = [];
				
				$idx = 0;
				foreach ($members_id as $member_id)
				{
					$members []= DBHelper::select_row('users', ['USER_ID', 'EMAIL', 'NAME'], [
						'USER_ID' => $member_id['USER_ID']
					]);
					$members[$idx]['ACCESS_TYPE'] = $member_id['ACCESS_TYPE'];
					$idx++;
				}

				usort($members, 'sortUser');

				echo json_encode($members);

				exit;

			case 'get_todo_files':

				$todo_id 	= $_GET['todo_id'];
				$files 		= DBHelper::select('files', ['*'], [
					'TODO_ID'	=> $todo_id
				]);
				echo json_encode($files);

				exit;
		}		

		break;

	case 'POST':

		// evaluate what action is requested by client
		$action = $_POST['action'];
		switch ($action)
		{
			case 'login':

				// check if user entered all required parameters
				if (isset($_POST['email'], $_POST['password']))
				{
					$email 		= $_POST['email'];
					$password 	= md5($_POST['password']);
					
					$user 		= DBHelper::select_row('users', ['*'], [
						'EMAIL'		=> $email,
						'PASSWORD'	=> $password
					]);

					// if user was not found, wrong credentials
					if ($user == FALSE)
					{
						$response['error'] 		= TRUE;
						$response['error_msg']	= 'Login credentials are wrong. Please try again.';
					}
					else
					{
						$response['user']['user_id']	= $user['USER_ID'];
						$response['user']['email']		= $user['EMAIL'];
						$response['user']['name']		= $user['NAME'];
					}
				}
				else
				{
					$response['error'] 		= TRUE;
					$response['error_msg']	= 'Required parameters email or password is missing.';
				}

				echo json_encode($response);

				exit;

			case 'insert_todo_list':

				$list_name	= $_POST['list_name'];
				$user_id 	= $_POST['user_id'];
				
				do
				{
					$list_id 	= mt_rand();
					$is_duplicate = DBHelper::select_row('todo_lists', ['*'], [
						'LIST_ID'	=> $list_id
					]);
				}
				while ($is_duplicate);

				if (!DBHelper::insert('todo_lists', [
						'LIST_ID'	=> $list_id,
						'LIST_NAME'	=> $list_name
					]))
				{
					$response['status'] = 1;
					echo json_encode($response);
					exit;
				}

				if (!DBHelper::insert('list_access', [
						'USER_ID'		=> $user_id,
						'LIST_ID'		=> $list_id,
						'ACCESS_TYPE'	=> 0
					]))
				{
					$response['status'] = 2;
					echo json_encode($response);
					exit;
				}

				$response['status'] 	= 0;
				$response['list_id']	= $list_id;
				$response['list_name']	= $list_name;
				echo json_encode($response);

				exit;

			case 'insert_todo_item':
				
				do
				{
					$task_id = mt_rand();
					$is_duplicate = DBHelper::select_row('todo_items', ['TODO_ID'], [
						'TODO_ID'	=> $task_id
					]);
				}
				while ($is_duplicate);

				$insert_type = $_POST['insert_type'];
				switch ($insert_type)
				{
					case 'quick_add':
						
						$task_name 	= $_POST['task_name'];
						$list_id 	= $_POST['list_id'];
						$completed 	= 0;

						if (!DBHelper::insert('todo_items', [
								'TODO_ID'		=> $task_id,
								'LIST_ID'		=> $list_id,
								'ITEM_DESC'		=> $task_name,
								'IS_COMPLETED'	=> $completed
							]))
						{
							$response['status'] = 1;
							echo json_encode($response);
							exit;
						}

						$todo_item = DBHelper::select_row('todo_items', ['*'], [
							'TODO_ID'	=> $task_id
						]);
						$response['TODO_ID']		= $todo_item['TODO_ID'];
						$response['LIST_ID']		= $todo_item['LIST_ID'];
						$response['ITEM_DESC']		= $todo_item['ITEM_DESC'];
						$response['NOTE']			= $todo_item['NOTE'];
						$response['DUE_DATE']		= $todo_item['DUE_DATE'];
						$response['IS_COMPLETED']	= $todo_item['IS_COMPLETED'];
						$response['status'] 		= 0;
						echo json_encode($response);	

						exit;
					
					case 'regular_add':

						$task_name 	= $_POST['task_name'];
						$list_id   	= $_POST['list_id'];
						$due_date 	= empty($_POST['due_date']) ? null : $_POST['due_date'];
						$note		= empty($_POST['note']) ? null : $_POST['note'];
						$completed 	= 0;

						if (!DBHelper::insert('todo_items', [
								'TODO_ID'		=> $task_id,
								'LIST_ID'		=> $list_id,
								'ITEM_DESC'		=> $task_name,
								'DUE_DATE'		=> $due_date,
								'NOTE'			=> $note,
								'IS_COMPLETED'	=> $completed
							]))
						{
							$response['status'] 			= 1;
							$response['list_id']			= $list_id;
							$response['generated_todo_id']	= $task_id;
							echo json_encode($response);
							exit;
						}

						$response['todo_id']		= $task_id;
						$response['list_id']		= $list_id;
						$response['item_desc']		= $task_name;
						$response['due_date']		= $due_date;
						$response['note']			= $note;
						$response['is_completed']	= $completed;
						$response['status'] 		= 0;
						$response['debug']			= $_POST;
						echo json_encode($response);

						exit;
				}

				$response['status'] = 2;
				echo json_encode($response);

				exit;

			case 'update_todo_list':
				
				$user_id 		= $_POST['user_id'];
				$list_id 		= $_POST['list_id'];
				$new_list_name	= $_POST['new_list_name'];

				$list_access = DBHelper::select_row('list_access', ['ACCESS_TYPE'], [
					'LIST_ID'	=> 	$list_id,
					'USER_ID'	=>	$user_id
				]);

				if ($list_access['ACCESS_TYPE'] != 0)
				{
					$response['status'] = 1;
					echo json_encode($response);
					exit;
				}

				if (!DBHelper::update('todo_lists', [
						'LIST_NAME'	=> $new_list_name
					], [
						'LIST_ID'	=> $list_id
					]))
				{
					$response['status'] = 2;
					echo json_encode($response);
					exit;
				}

				$response['status'] 	= 0;
				$response['list_id']	= $list_id;
				echo json_encode($response);

				exit;

			case 'update_todo_item':

				$todo_id 	= $_POST['todo_id'];
				$task_name 	= $_POST['task_name'];
				$list_id   	= $_POST['list_id'];
				$due_date 	= $_POST['due_date'] == ""? null : $_POST['due_date'];
				$note		= $_POST['note'] == ""? null : $_POST['note'];
				$completed 	= $_POST['is_completed'];

				if (!DBHelper::update('todo_items', [
						'ITEM_DESC'		=> $task_name,
						'DUE_DATE'		=> $due_date,
						'NOTE'			=> $note,
						'IS_COMPLETED'	=> $completed
					], [
						'TODO_ID'		=> $todo_id
					]))
				{
					$response['status'] = 1;
					echo json_encode($response);
					exit;
				}

				$response['status'] 		= 0;
				$response['todo_id']		= $todo_id;
				$response['item_desc']		= $task_name;
				$response['list_id']		= $list_id;
				$response['due_date']		= $due_date;
				$response['note']			= $note;
				$response['is_completed']	= $completed;
				echo json_encode($response);

				exit;

			case 'delete_todo_list':

				$list_id = $_POST['list_id'];
				$user_id = $_POST['user_id'];
				
				$list_access = DBHelper::select_row('list_access', ['ACCESS_TYPE'], [
					'USER_ID'	=> $user_id,
					'LIST_ID'	=> $list_id
				]);
				$response['list_name'] = DBHelper::select_row('todo_lists', ['LIST_NAME'], [
					'LIST_ID' => $list_id
				]);
				$response['list_name'] = $response['list_name']['LIST_NAME'];

				if ($list_access['ACCESS_TYPE'] != 0)
				{
					$response['status'] = 1;
					echo json_encode($response);
					exit;
				}

				if (!DBHelper::delete('todo_items', [
						'LIST_ID'	=> $list_id
					]))
				{
					$response['status'] = 2;
					echo json_encode($response);
					exit;
				}

				if (!DBHelper::delete('list_access', [
						'LIST_ID'	=> $list_id
					]))
				{
					$response['status'] = 2;
					echo json_encode($response);
					exit;
				}				

				if (!DBHelper::delete('todo_lists', [
						'LIST_ID'	=> $list_id
					]))
				{
					$response['status'] = 2;
					echo json_encode($response);
					exit;
				}

				$response['status'] 	= 0;
				echo json_encode($response);

				exit;

			case 'delete_todo_item':
				break;

			case 'share_todo_list':
				
				$email = $_POST['email'];
				$user = DBHelper::select_row('users', ['USER_ID'], ['EMAIL' => $email]);
				if (!$user)
				{
					$response['status'] = 1; // user not found
					echo json_encode($response);
					exit;
				}

				$list_id = $_POST['list_id'];
				if (DBHelper::select_row('list_access', ['USER_ID'], [
						'USER_ID'	=> $user['USER_ID'],
						'LIST_ID'	=> $list_id
					]))
				{
					$response['status'] = 2; // already member
					echo json_encode($response);
					exit;
				}

				if (!DBHelper::insert('list_access', [
						'USER_ID' 		=> $user['USER_ID'],
						'LIST_ID'		=> $list_id,
						'ACCESS_TYPE'	=> 1
					]))
				{
					$response['status'] = 3; // insert failed
					echo json_encode($response);
					exit;
				}

				$user = DBHelper::select_row('users', ['USER_ID', 'EMAIL', 'NAME'], ['EMAIL' => $email]);
				$response['USER_ID']	= $user['USER_ID'];
				$response['EMAIL']		= $user['EMAIL'];
				$response['NAME']		= $user['NAME'];
				$response['status'] 	= 0;
				echo json_encode($response);

				exit;

			case 'upload_file':

				do
				{
					$file_id = mt_rand();
					$is_duplicate = DBHelper::select_row('files', ['FILE_ID'], [
						'FILE_ID' => $file_id
					]);
				}
				while ($is_duplicate);
				
				$todo_id 	= $_POST['todo_id'];
				$file_name	= basename($_FILES['uploaded_file']['name']);
				$file_path 	= 'uploads/' . $file_name;
				if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $file_path))
				{
					DBHelper::insert('files', [
						'FILE_ID'	=> $file_id,
						'TODO_ID'	=> $todo_id,
						'FILENAME'	=> $file_name
					]);
				}

				exit;

			case 'delete_item_files':

				$file_id = $_POST['file_id'];
				$file = DBHelper::select_row('files', ['*'], ['FILE_ID' => $file_id]);
				@unlink('uploads/' . $file['FILENAME']);
				DBHelper::delete('files', ['FILE_ID' => $file_id]);
				$response['status'] = 0;
				echo json_encode($response);

				exit;

			case 'update_item_status':

				$completed 	= $_POST['is_completed'];
				$todo_id 	= $_POST['todo_id'];
				if (!DBHelper::update('todo_items', ['IS_COMPLETED' => $completed], [
						'TODO_ID'	=> $todo_id
					]))
				{

				}

				exit;
		}

		break;
}

echo json_encode($response); // unknown attempt response