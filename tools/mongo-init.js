const hello = db.hello();
if (hello.setName !== 'minimart' && !hello.isreplicaset) {
    throw new Error('Port 27018 belongs to another MongoDB deployment');
}
try {
    void rs.status();
} catch (error) {
    if (error.code !== 94) throw error;
    void rs.initiate({_id: 'minimart', members: [{_id: 0, host: '127.0.0.1:27018'}]});
}
