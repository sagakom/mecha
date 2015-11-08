<form class="comment-form" id="comment-form" action="<?php echo $article->url; ?>" method="post">
  <?php echo $messages; ?>
  <?php echo Form::hidden('token', $token); ?>
  <?php echo Form::hidden('parent', ""); ?>
  <label class="grid-group">
    <span class="grid span-1 form-label"><?php echo $speak->comment_name; ?></span>
    <span class="grid span-5"><?php echo Form::text('name', Guardian::wayback('name'), null, array('class' => 'input-block')); ?></span>
  </label>
  <label class="grid-group">
    <span class="grid span-1 form-label"><?php echo $speak->comment_email; ?></span>
    <span class="grid span-5"><?php echo Form::email('email', Guardian::wayback('email'), null, array('class' => 'input-block')); ?></span>
  </label>
  <label class="grid-group">
    <span class="grid span-1 form-label"><?php echo $speak->comment_url; ?></span>
    <span class="grid span-5"><?php echo Form::url('url', Guardian::wayback('url'), null, array('class' => 'input-block')); ?></span>
  </label>
  <label class="grid-group">
    <span class="grid span-1 form-label"><?php echo $speak->comment_message; ?></span>
    <span class="grid span-5"><?php echo Form::textarea('message', Guardian::wayback('message'), null, array('class' => 'textarea-block')); ?></span>
  </label>
  <?php Weapon::fire('comment_form_input', array($article)); ?>
  <label class="grid-group">
    <span class="grid span-1 form-label"><?php echo Guardian::math(); ?> =</span>
    <span class="grid span-5"><?php echo Form::text('math', "", null, array('autocomplete' => 'off')); ?></span>
  </label>
  <div class="grid-group">
    <span class="grid span-1"></span>
    <div class="grid span-5">
      <p><?php echo Form::button($speak->publish, null, 'submit', null, array('class' => array('btn', 'btn-construct'))); ?></p>
      <p><?php echo $speak->comment_guide; ?></p>
    </div>
  </div>
</form>