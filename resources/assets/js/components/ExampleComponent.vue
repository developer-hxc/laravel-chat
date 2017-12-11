<template>
    <div>
        <div>
            <ul>
                <li v-for="data in chatData" :class="data.uid == uid?'t_right':''">
                    <span>{{data.uid}} {{data.time}}</span>
                    <br>
                    <span>{{data.message}}</span>
                </li>
            </ul>
        </div>
        <Input v-model="value6" type="textarea" :rows="4" placeholder="Enter something..."></Input>
        <br>
        <div class="t_right">
            <Button type="primary" @click="send">发送</Button>
        </div>
        <Modal
                v-model="modal4"
                title="请输入您的昵称"
                ok-text="确认"
                cancel-text="关闭"
                @on-ok="login">
            <Input v-model="uid"></Input>
        </Modal>
    </div>
</template>

<script>
    export default {
        data(){
            return {
                value6:'',
                modal4:false,
                uid:'',
                ws:'',
                chatData:[]
            }
        },
        created(){
            this.modal4 = true
            this.webSoket()
        },
        methods:{
            webSoket(){
                this.ws = new WebSocket('ws://127.0.0.1:1234')
                this.ws.onmessage = this.onMessage
            },
            login(){
                let data = {
                    uid : this.uid
                }
                this.wsSend(data)
            },
            onMessage(e){
                this.chatData.push(JSON.parse(e.data));
            },
            send(){
                let data = {
                    message:this.value6,
                    uid:this.uid
                }
                this.wsSend(data)
                this.value6 = ''

            },
            wsSend(data){
                this.ws.send(JSON.stringify(data))
            }

        }

    }
</script>

<style>
    .t_right{
        text-align: right;
    }
</style>