<?php if(session('status')): ?>
    <div class="alert alert-success">
        <?php echo e(session('status')); ?>

    </div>
<?php endif; ?>

<?php if($errors->any()): ?>
    <div class="alert alert-danger">
        <div class="font-weight-bold mb-1">Ошибка</div>
        <ul class="mb-0">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<?php /**PATH /var/www/html/resources/views/partials/flash.blade.php ENDPATH**/ ?>