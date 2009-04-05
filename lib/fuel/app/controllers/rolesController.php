<?php
class RolesController extends Controller {
	
	public function index() {
		
		$q = "SELECT `app_accounts`.`username`,`app_roles`.* "
			."FROM `app_accounts`,`app_roles` "
			."WHERE `app_accounts`.`objId`=`app_roles`.`accountId` "; 
		$users_results = _db()->queryAll($q,FDATABASE_FETCHMODE_ASSOC);
		
		$q= "DESCRIBE `app_roles`";
		$roles_results = _db()->query($q);

		$defined_roles = array();
		
		while ($role = $roles_results->fetchRow(FDATABASE_FETCHMODE_ASSOC)) {
			if ($role['Field'] == "accountId") {continue;}
			$defined_roles[] = $role;
		}

		$this->set('defined_roles',$defined_roles);
		$this->set('users',$users_results);
	}
	
	public function createUser($fn,$ln,$un,$pw,$email) {

 		// Create a user object to go along with the account
		$user = User::Create($un,$pw,$email);
 		//$user = User::Create(FAccountManager::Create($un,$pw,$email));
 		$user->setFirstName($fn,false);
 		$user->setLastName($ln,false);
 		$user->save();
 		
 		// Redirect the user to the login screen
 		$this->redirect("/login");
 	}
 	
 	public function deleteUser($objId) {
 		User::Delete($objId);
 		$this->redirect("/fuel/roles/");
 	}
 	
 	public function createAccount() {
 		if ($this->form) {
 			$id = FAccountManager::Create($this->form['username'],$this->form['password'],$this->form['email']);
 			$this->flash("A new account was created for user '{$this->form['username']}' with unique id '{$id}'");
 		} else {
			die("GET not supported. Try again using POST");
		}
 		$this->redirect("/fuel/roles/");
 	}
 	
 	public function defineRole() {
		if ($this->form) {
			$name    =& $this->form['name'];
			$desc    =& $this->form['desc'];
			$default = ("grant" == $this->form['default']) ? true : false;
			FAccount::DefineRole($name,$default,$desc);
			$this->flash("Defined new role '{$name}' with default policy '{$default}'");
			$this->redirect("/fuel/roles/");
		} else {
			die("GET not supported. Try again using POST");
		}
 	}
 	
 	public function deleteRole($name) {
		FAccount::DeleteRole($name);
		$this->flash("Deleted role '{$name}' ");
		$this->redirect("/fuel/roles/");
 	}
 	
 	public function getPower() {
 		if (false !== ($user = $this->requireLogin("/login"))) {
 			// Make the currently logged in user an Administrator
			$user->getFAccount()->grantRole("administrator");
			echo "SUPERP0WER!";
			die(); 			
 		}
 	}
 	public function relinquishPower() {
 	 	if (false !== ($user = $this->requireLogin("/login"))) {
 			// Make the currently logged in user an Administrator
			$user->getFAccount()->denyRole("administrator");
			echo "GRACIOUSLY ABDICATING P0WER";
			die(); 			
 		}
 	}
}
?>