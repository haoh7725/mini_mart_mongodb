if (!db.hello().isWritablePrimary) {
    throw new Error('Replica set is not ready');
}
print('MongoDB MiniMart ready: 127.0.0.1:27018 / replicaSet=minimart');
