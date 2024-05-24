

*****************************************************************************Elasticsearch（本质上是一个数据库）  简单了解

1.创建索引   （mysql 创建表）
curl -XPUT http://localhost:9200/test_index1    
//返回中如果包含了 "acknowledged" : true, 则代表请求成功。


2.查看刚刚创建的索引  
curl http://localhost:9200/test_index1
curl http://localhost:9200/test_index1?pretty    //?pretty 格式化返回的数据



3.在刚刚创建的索引中创建一个新的类型，对应的接口地址是 /{index_name}/_mapping      （mysql  添加字段）
curl -H'Content-Type: application/json' -XPUT http://localhost:9200/test_index1/_mapping?pretty -d'{
  "properties": {
    "title": { "type": "text", "analyzer": "ik_smart" }, 
    "description": { "type": "text", "analyzer": "ik_smart" },
    "price": { "type": "scaled_float", "scaling_factor": 100 }
  }
}'



4.接下来我们要往索引里面插入一些文档：    （mysql 插入记录 数据）
curl -H'Content-Type: application/json' -XPUT http://localhost:9200/test_index1/_doc/1?pretty -d'{
    "title": "iPhone X",
    "description": "新品到货",
    "price": 8848
}'


curl -H'Content-Type: application/json' -XPUT http://localhost:9200/test_index1/_doc/2?pretty -d'{
    "title": "OPPO R15",
    "description": "新品到货",
    "price": 2000
}'



5.用 ID 来获取指定的文档：
curl http://localhost:9200/test_index1/_doc/1?pretty



6.来试试 Elasticsearch 的搜索功能：
curl -XPOST -H'Content-Type:application/json' http://localhost:9200/test_index1/_doc/_search?pretty -d'
{
    "query" : { "match" : { "description" : "新" }}
}'



curl -XPOST -H'Content-Type:application/json' http://localhost:9200/test_index1/_doc/_search?pretty -d'
{
    "query" : { "match" : { "description" : "新品" }}
}'