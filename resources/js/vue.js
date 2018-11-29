/*jshint esversion: 6 */

/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
window.Vue = require('vue');

import VueRouter from 'vue-router';
Vue.use(VueRouter);

const menu = Vue.component('menu-restaurant', require('./components/menuRestaurant.vue'));

const routes = [
  { path: '/', redirect: '/menu' },
  { path: '/menu', component: menu },
];

const router = new VueRouter({
  routes:routes
});

const app = new Vue({
	router,
    mounted() {
        
    }
}).$mount('#app');


