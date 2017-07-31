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
				$todo_list_id = DBHelper::select('list_access', ['LIST_ID'], [
					'USER_ID' => $user_id
				]);


				$todo_lists = [];
				foreach ($todo_list_id as $list_id)
				{
					$todo_lists []= DBHelper::select_row('todo_lists', ['*'], [
						'LIST_ID'	=> $list_id['LIST_ID']
					]);
				}

				echo json_encode($todo_lists);

				exit;

			case 'get_todo_item':

				$list_id = $_GET['list_id'];

				$todo_items = DBHelper::select('todo_items', ['TODO_ID', 'ITEM_DESC', 'IS_COMPLETED'], [
					'LIST_ID'	=> $list_id
				]);

				echo json_encode($todo_items);

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

						$response['status'] = 0;
						echo json_encode($response);	

						exit;
					
					case 'regular_add':
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

				$response['status'] = 0;
				echo json_encode($response);

				exit;

			case 'update_todo_item':
				break;

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
				break;

			case 'upload_file':
				break;
		}

		break;
}

echo json_encode($response); // unknown attempt response