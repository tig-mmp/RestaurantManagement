<template>
    <div>
        <h3>Pending Orders</h3>
        <a class="btn btn-sm btn-success" v-show="!hide" v-on:click.prevent="setHide(true)">hide</a>
        <a class="btn btn-sm btn-success" v-show="hide" v-on:click.prevent="setHide(false)">show</a>
        <table class="table" v-show="!hide">
            <thead>
            <tr>
                <th scope="col">image</th>
                <th scope="col">name</th>
                <th scope="col">state</th>
                <th scope="col">table_number</th>
                <th scope="col">Actions</th>
            </tr>
            </thead>
            <tbody>
            <tr class="color-box" v-for="order in orders" :key="order.id"
                v-bind:style="[order.state === 'confirmed' ? {'background-color' : 'Crimson'} : {'background-color' : 'LightSkyBlue'}]">
                <img :src="'/imgItems/' + order.photo_url" class="rounded-circle border border-warning" width="25" height="25" >
                <td>{{order.name}}</td>
                <td>{{order.state}}</td>
                <td>{{order.table_number}}</td>
                <td>
                    <a v-show="issetButton(order.id)" class="btn btn-sm btn-success" v-on:click.prevent="deleteOrder(order.id)">Cancel</a>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
    module.exports= {
        props: ['newOrder', 'userId', 'mealId', 'removeOrders'],
        data: function () {
            return {
                orders: [],
                deleteButton: [],
                meal_id: '',
                hide: false
            }
        },
        methods: {
            setHide(decisao){
                this.hide = decisao;
            },
            deleteOrder(id){
                axios.delete('/api/orders/' + id)
                    .then(function (response) {})
                    .catch(function (error) {
                        console.log(error);
                    });
                this.orders.splice(this.orders.findIndex(v => v.id === id), 1);
                this.hideButton(id);
            },
            hideButton(id){
                this.deleteButton.splice(this.deleteButton.findIndex(v => v.id === id), 1);
            },
            timeoutOrder: function(id){
                //continua ter meal_id mesmo se fechar orders
                this.meal_id = JSON.parse(JSON.stringify(this.mealId));
                setTimeout(() => {
                    this.hideButton(id);
                    this.confirmOrder(id);
                }, 5000);
            },
            confirmOrder(id){
                axios.put('/api/orders/'+id, {'state' : 'confirmed'})
                .then(response=>{
                    this.$socket.emit('orderAddCook', response.data.data);
                    this.$socket.emit('updateOrder', response.data.data.meal_id);
                    //envia pedido para o maneger para ele atualizar a lista de orders de uma especifica meal
                    //this.$socket.emit('orderCreated', this.meal_id);
                    this.$emit('order-confirmed');
                    this.orders.splice(this.orders.findIndex(v => v.id === id), 1);
                    this.orders.unshift(response.data.data);
                    axios.put('/api/meals/'+response.data.data.meal_id, {'price' : this.newOrder.price}).
                    then(response=>{});
                }).catch(error => {});
            },
            issetButton(id){
                return this.deleteButton.find(function(val){return val === id;});
            },
            getPendingOrders(){
                axios.get('api/users/waiter/'+this.userId+'/pendingOrders').then(response=>{
                    if (response.data != '') {
                        this.orders = response.data.data;
                    }
                }).catch(function (error) {
                    console.log(error);
                });
            }
        },
        mounted() {
            this.getPendingOrders();
        },
        watch: {
            newOrder: function (order) {
                this.orders.unshift(order);
                this.deleteButton.push(order.id);
                this.timeoutOrder(order.id);
            },
            removeOrders: function (ordersRecived) {
                ordersRecived.forEach((orderId) => {
                    this.orders.splice(this.orders.findIndex(order => order.id === orderId), 1);
                });
            }
        },
        sockets: {
            waiterRemovePending(orderId){
                this.orders.splice(this.orders.findIndex(order => order.id === orderId), 1);
            },
            waiterUpdateOrders(){
                this.orders = [];
                this.getPendingOrders();
            },
            waiterRemovePendingWithoutToast(orderId){
                this.orders.splice(this.orders.findIndex(order => order.id === orderId), 1);
            },
        }
    }
</script>
