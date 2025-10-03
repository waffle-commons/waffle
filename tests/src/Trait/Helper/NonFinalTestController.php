<?php

namespace WaffleTests\Trait\Helper;

/**
 * A helper class that violates security rule #8 by containing "Controller" in its name
 * but not being declared as final.
 */
class NonFinalTestController
{
}
