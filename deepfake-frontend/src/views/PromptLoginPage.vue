<template>
  <div class="prompt-login">
    <h2>🚫 Access Restricted</h2>
    <p>You need to log in to train a generator model.</p>
    <router-link to="/login" class="btn">Go to Login</router-link>
    <router-link to="/register" class="btn">Register</router-link>
  </div>
</template>

<style scoped>
.prompt-login {
  text-align: center;
  margin-top: 120px;
  color: #fff;
}
.btn {
  display: inline-block;
  margin: 10px;
  padding: 12px 24px;
  border-radius: 6px;
  background: linear-gradient(90deg, #00f0ff, #ff00ff);
  color: #fff;
  text-decoration: none;
  font-weight: bold;
}
</style>
