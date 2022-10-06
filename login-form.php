<div class="container">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="form-wrap">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="w-100">
                    <!-- Email input -->
                    <h1 class="text-center mb-4">Log In to Start Syncing</h1>
                    <div class="form-outline mb-4">
                        <input type="text" id="user" class="form-control" name="user" />
                        <label class="form-label" for="user">Username</label>
                    </div>

                    <!-- Password input -->
                    <div class="form-outline mb-4">
                        <input type="password" id="pass" class="form-control" name="pass"/>
                        <label class="form-label" for="pass">Password</label>
                    </div>
                    <input type="submit" class="btn btn-primary btn-block w-100 mb-4" value="Log In"/>
                </form>
            </div>
        </div>
    </div>
</div>
<style>
    .form-wrap {
        display: flex;
        width: 100%;
        height: 100vh;
        place-items: center;
    }
</style>