<div class="block">
    <div class="centered">
        <?php
            echo $this->Session->flash('auth');
            echo $this->Form->create('User');
            echo $this->Form->inputs(array(
                'legend' => __('Login', true),
                'email',
                'password'
            ));
            echo $this->Form->button(__('Login'), array('class' => 'btn btn-primary'));
            echo $this->Form->end();?>
    </div>

</div>