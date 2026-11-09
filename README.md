# EasySwoole SPL

提供 `SplBean`、`SplArray`、字符串、流和链表等基础工具。

## SplBean

`SplBean` 将声明的 public / protected 非静态属性用于数据还原和序列化，忽略未知输入及 private 属性。

构造顺序保持为：初始化属性和转换配置，使用输入数据执行 `restore()`，最后执行 `initialize()`。构造器提升属性（promoted properties）若已由 PHP 初始化，保留其值，避免 nullable 参数被重置为 null，也避免重复初始化 readonly 属性；明确传入的还原数据仍按原有 `restore()` 与 PHP readonly 规则处理。

```php
use EasySwoole\Spl\SplBean;

class Member extends SplBean
{
    public function __construct(public ?string $name)
    {
        parent::__construct();
    }
}

$member = new Member('Alice');
$member->toArray(); // ['name' => 'Alice']
```

## 反射缓存

`SplBean` 按实际运行时类缓存 `ReflectionProperty` 和 `ReflectionAttribute` 描述符，构造和序列化时复用，减少大量同类型 Bean 的重复反射。缓存仅保存类级元数据；各实例仍独立构造属性转换注解、登记转换配置及创建默认业务对象，继承类之间分别缓存。

缓存不会改变属性转换、`restore()`、`initialize()` 的调用顺序及过滤、序列化行为；构造器提升属性的保留规则同上。缓存建立完成后才执行属性注解构造器，支持注解构造过程中的重入。

所有现有 public / protected 方法的签名和转换、过滤、序列化接口保持兼容。
