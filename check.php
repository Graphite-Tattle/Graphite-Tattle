<?php
include 'inc/init.php';

fAuthorization::requireLoggedIn();

fRequest::overrideAction();
$action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete'));
$check_type = fRequest::getValid('type', array('predictive', 'threshold'));
$sort = fCRUD::getSortColumn(array('name','target','warn','error','status','timestamp','count'));
$sort_dir  = fCRUD::getSortDirection('asc');

$check_id = fRequest::get('check_id', 'integer');

$check_list_url = Check::makeURL('list', $check_type);

$breadcrumbs[] = array('name' => ucfirst($check_type) . ' Checks', 'url' => Check::makeURL('list', $check_type), 'active'=> false);
// --------------------------------- //
if ('delete' == $action) {
  try {
    $obj = new Check($check_id);
    $delete_text = 'Are you sure you want to delete the check : <strong>' . $obj->getName() . '</strong>?';
    if (fRequest::isPost()) {
      fRequest::validateCSRFToken(fRequest::get('token'));
      $obj->delete();
      // Do our own Subscription and CheckResult cleanup instead of using ORM
      $subscriptions = Subscription::findAll($check_id);
      foreach ($subscriptions as $subscription) {
        $subscription->delete();
      }
      $check_results = CheckResult::findAll($check_id);
      foreach ($check_results as $check_result) {
        $check_result->delete();
      }
      fMessaging::create('success', fURL::get(), 
                         'The check ' . $obj->getName() . ' was successfully deleted');
      fURL::redirect($check_list_url);	
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', fURL::get(), 
                       'The check requested, ' . fHTML::encode($date) . ', could not be found');
    fURL::redirect($check_list_url);
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }
	
  include VIEW_PATH . '/delete.php';	

// --------------------------------- // 
} elseif ('edit' == $action) {
  try {
    $check = new Check($check_id);
    if (fRequest::isPost()) {
      $check->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $check->store();
			
      fMessaging::create('affected', fURL::get(), $check->getName());
      fMessaging::create('success', fURL::get(), 
                         'The check ' . $check->getName(). ' was successfully updated');
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', fURL::get(), 
                       'The check requested, ' . fHTML::encode($check_id) . ', could not be found');	
    fURL::redirect($check_list_url);
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }

  include VIEW_PATH . '/add_edit.php';
	
// --------------------------------- //
} elseif ('add' == $action) {
  $check = new Check();
  if (fRequest::isPost()) {	
    try {
      $check->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $check->store();
			
      fMessaging::create('affected', fURL::get(), $check->getName());
      fMessaging::create('success', fURL::get(), 
                         'The check ' . $check->getName() . ' was successfully created');
      fURL::redirect($check_list_url);
    } catch (fExpectedException $e) {
      fMessaging::create('error', fURL::get(), $e->getMessage());	
    }	
  } 

  include VIEW_PATH . '/add_edit.php';	
	
} else {
  $checks = Check::findAll($check_type,$sort,$sort_dir);
  include VIEW_PATH .'/list_checks.php';
}
