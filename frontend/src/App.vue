<template>
  <div id="app">
    <header class="app-header">
      <div class="header-inner">
        <router-link to="/" class="app-logo">AccountSplit</router-link>
      </div>
    </header>
    <main class="app-main">
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.app-header {
  background: #1a73e8;
  color: #fff;
  padding: 0 1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.15);
}
.header-inner {
  max-width: 900px;
  margin: 0 auto;
  height: 56px;
  display: flex;
  align-items: center;
}
.app-logo {
  color: #fff;
  text-decoration: none;
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: 0.5px;
}
.app-main {
  max-width: 900px;
  margin: 2rem auto;
  padding: 0 1rem;
}
</style>