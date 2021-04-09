# group_default_quota

Set default user quotas for group members

## Usage

### Set the default quota for a group

```
 occ group_default_quota:set admin 20GB
```

When a user is a member of multiple groups with a default quota set, the maximum quota will be used.

To remove the configured default for a group set it to `default`.

### Get the configured default quota for a group

```
 occ group_default_quota:get admin
```

### Lists the configured default quotas

```
 occ group_default_quota:list
```
